# shellcheck shell=bash
# The test suites: Pest and Vitest in the testing environment, the browser suite in e2e.
#
#   mtav pest [args]      Pest's own arguments
#   mtav vitest [args]    Vitest's own arguments
#   mtav e2e [args]       Pest's own arguments, for the Browser suite
#   mtav test [--pest args…] [--vitest args…] [--e2e args…]
#   mtav precommit [--pest args…] [--vitest args…]
#
# `test` and `precommit` run several runners, so each runner's arguments follow its own
# flag: a Pest flag never reaches Vitest.

PEST_SUITES='Arch|Unit|UseCases|Stress'

# Sets PEST_ARGS: the arguments, after the default suites unless they name their own
# (an array, so that --filter="runs the lottery" stays one argument)
pest_args() {
    local args=("$@") i suites=""
    for ((i = 0; i < ${#args[@]}; i++)); do
        case "${args[$i]}" in
            --testsuite)
                if [ $((i + 1)) -ge ${#args[@]} ]; then
                    fail "❌ --testsuite flag provided but no value specified"
                    return 1
                fi
                suites="${args[$((i + 1))]}"
                ;;
            --testsuite=*) suites="${args[$i]#--testsuite=}" ;;
        esac
    done

    if [ -z "$suites" ]; then
        PEST_ARGS=(--testsuite "Arch,Unit,UseCases" "${args[@]}")
    elif [[ "$suites" =~ ^($PEST_SUITES)(,($PEST_SUITES))*$ ]]; then
        PEST_ARGS=("${args[@]}")
    else
        fail "❌ Invalid testsuite: $suites"
        echo "Valid values (comma-separated): ${PEST_SUITES//|/, }" >&2
        return 1
    fi
}

# Sets RUNNER_ARGS_PEST, RUNNER_ARGS_VITEST and RUNNER_ARGS_E2E from
# `[--pest args…] [--vitest args…] [--e2e args…]`; the flags allowed are the arguments
split_runner_args() {
    local allowed=("$@") arg current="" flag
    RUNNER_ARGS_PEST=()
    RUNNER_ARGS_VITEST=()
    RUNNER_ARGS_E2E=()

    for arg in "${RUNNER_ARGV[@]}"; do
        for flag in "${allowed[@]}"; do
            if [ "$arg" = "--$flag" ]; then
                current="$flag"
                continue 2
            fi
        done
        case "$current" in
            pest) RUNNER_ARGS_PEST+=("$arg") ;;
            vitest) RUNNER_ARGS_VITEST+=("$arg") ;;
            e2e) RUNNER_ARGS_E2E+=("$arg") ;;
            *)
                fail "❌ '$arg' names no runner: put each runner's arguments after its flag"
                echo "Usage: mtav $RUNNER_COMMAND$(printf ' [--%s args]' "${allowed[@]}")" >&2
                echo "  e.g. mtav $RUNNER_COMMAND --pest --filter=lottery --vitest plan" >&2
                return 1
                ;;
        esac
    done
}

# Starts the testing environment unless it already runs, with the dependencies installed
# (Node's too when Vitest runs); testing_down stops it only when this run started it
testing_up() {
    local with_node="$1"

    TESTING_STARTED_HERE=true
    is_running testing php && TESTING_STARTED_HERE=false

    step "Starting testing environment..."
    env_setup
    if ! compose testing up --build --detach --wait --wait-timeout 120 --quiet-pull; then
        fail "❌ the testing environment did not start"
        return 1
    fi

    step "Installing PHP dependencies..."
    if ! cexec testing php composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader; then
        fail "❌ composer install failed"
        testing_down
        return 1
    fi

    if [ "$with_node" = true ]; then
        step "Installing Node dependencies..."
        if ! cexec testing assets pnpm install; then
            fail "❌ pnpm install failed"
            testing_down
            return 1
        fi
    fi
}

testing_down() {
    if [ "$TESTING_STARTED_HERE" = true ]; then
        step "Stopping testing environment..."
        compose testing down || true
    else
        step "Leaving the testing environment running, as it was found"
    fi
}

# Prints how a runner did, and returns it
report() {
    local status="$1" name="$2"
    echo ""
    if [ "$status" -eq 0 ]; then
        ok "✅ $name passed"
    else
        fail "❌ $name failed"
    fi
    return "$status"
}

run_pest() {
    banner "📦 PHP Tests (Pest)"
    pest_args "$@" || return 1
    local status=0
    cexec testing php php artisan test "${PEST_ARGS[@]}" || status=$?
    report "$status" "PHP tests"
}

run_vitest() {
    banner "🎨 Frontend Tests (Vitest)"
    local status=0
    cexec testing assets pnpm run test --run "$@" || status=$?
    report "$status" "Frontend tests"
}

# The browser suite in its own environment (docker/e2e): one container with PHP, Node,
# Playwright and its browsers, where Pest's browser plugin serves the app in-process.
# Runs alongside dev and testing; started and stopped every time.
run_e2e() {
    banner "🌐 E2E Tests (Playwright)"

    step "Starting e2e environment..."
    env_setup
    if ! compose e2e up --build --detach --wait --wait-timeout 120 --quiet-pull; then
        fail "❌ the e2e environment did not start"
        compose e2e down || true
        return 1
    fi

    step "Installing PHP dependencies..."
    if ! cexec e2e e2e composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader; then
        fail "❌ composer install failed"
        compose e2e down || true
        return 1
    fi

    step "Installing Node dependencies..."
    if ! cexec e2e e2e pnpm install; then
        fail "❌ pnpm install failed"
        compose e2e down || true
        return 1
    fi

    # Public files (avatars) are served through public/storage, as in every deployment;
    # a fresh checkout (CI) has no link yet
    cexec e2e e2e php artisan storage:link --relative --no-interaction --force > /dev/null

    step "Building assets..."
    if ! cexec e2e e2e pnpm run build; then
        fail "❌ asset build failed"
        e2e_down
        return 1
    fi

    # Reverb in the same container, so the browser's ws://localhost:8080 connections are real
    step "Starting Reverb (in-container websockets)..."
    compose e2e exec -d e2e php artisan reverb:start --host=127.0.0.1 --port=8080 || true

    step "Running Browser journeys..."
    local status=0
    cexec e2e e2e php artisan test --testsuite Browser "$@" || status=$?
    report "$status" "E2E tests" || true

    e2e_down
    return "$status"
}

# Built assets removed so the host stays clean; public/hot never touched (a dev server
# running at the same time would lose it)
e2e_down() {
    step "Removing built assets (public/build)..."
    cexec e2e e2e sh -lc 'rm -rf /var/www/html/public/build || true'
    step "Stopping e2e environment..."
    compose e2e down || true
}

# The runners' outcome, as the command's exit status
finish() {
    echo ""
    if [ "$1" = true ]; then
        ok "🎉 All tests passed!"
        exit 0
    fi
    fail "💥 Some tests failed"
    exit 1
}

suite_header() {
    info "🧪 Test Suite"
    info "════════════"
    local part
    for part in "$@"; do
        echo "  • $part"
    done
}

# mtav pest [args]
cmd_pest() {
    # Arguments it refuses stop it before anything starts
    pest_args "$@" || exit 1
    suite_header "PHP (Pest)"
    testing_up false || exit 1
    local passed=true
    run_pest "$@" || passed=false
    testing_down
    finish "$passed"
}

# mtav vitest [args]
cmd_vitest() {
    suite_header "Frontend (Vitest)"
    testing_up true || exit 1
    local passed=true
    run_vitest "$@" || passed=false
    testing_down
    finish "$passed"
}

# mtav e2e [args]
cmd_e2e() {
    suite_header "E2E (Playwright)"
    local passed=true
    run_e2e "$@" || passed=false
    finish "$passed"
}

# mtav precommit [--pest args…] [--vitest args…]: without the slow group
cmd_precommit() {
    RUNNER_COMMAND="precommit"
    RUNNER_ARGV=("$@")
    split_runner_args pest vitest || exit 1
    pest_args "${RUNNER_ARGS_PEST[@]}" || exit 1
    suite_header "PHP (Pest)" "Frontend (Vitest)"
    testing_up true || exit 1
    local passed=true
    run_pest "${RUNNER_ARGS_PEST[@]}" --exclude-group slow || passed=false
    run_vitest "${RUNNER_ARGS_VITEST[@]}" || passed=false
    testing_down
    finish "$passed"
}

# mtav test [--pest args…] [--vitest args…] [--e2e args…]
cmd_test() {
    RUNNER_COMMAND="test"
    RUNNER_ARGV=("$@")
    split_runner_args pest vitest e2e || exit 1
    pest_args "${RUNNER_ARGS_PEST[@]}" || exit 1
    suite_header "PHP (Pest)" "Frontend (Vitest)" "E2E (Playwright)"
    testing_up true || exit 1
    local passed=true
    run_pest "${RUNNER_ARGS_PEST[@]}" || passed=false
    run_vitest "${RUNNER_ARGS_VITEST[@]}" || passed=false
    testing_down
    run_e2e "${RUNNER_ARGS_E2E[@]}" || passed=false
    finish "$passed"
}
