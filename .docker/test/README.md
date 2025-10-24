# MTAV Docker Test Suite

Comprehensive test suite for all MTAV Docker functionality, designed to verify build processes, deployment systems, and development environments.

## Test Structure

The test suite is organized into focused test scripts that cover different aspects of the MTAV Docker system:

### Test Scripts

- **`common.sh`** - Shared test utilities, assertion functions, and common setup
- **`scripts.sh`** - Tests for `.docker/scripts/*` functionality
- **`build.sh`** - Tests for Docker image building processes
- **`mtav.sh`** - Tests for the main `mtav` command interface
- **`dev.sh`** - Tests for development environment (compose, containers, tools)
- **`prod.sh`** - Tests for production deployment and container optimization
- **`run-all.sh`** - Master test runner that executes all test suites

## Running Tests

### Run All Tests

```bash
# Run the complete test suite
.docker/test/run-all.sh
```

### Run Individual Test Suites

```bash
# Test build system
.docker/test/build.sh

# Test mtav command
.docker/test/mtav.sh

# Test development environment
.docker/test/dev.sh

# Test production deployment
.docker/test/prod.sh

# Test docker scripts
.docker/test/scripts.sh
```

## Test Features

### Comprehensive Coverage

- ✅ **Build System**: Image building, tagging, multi-stage builds
- ✅ **Command Interface**: mtav wrapper, parameter passing, error handling
- ✅ **Development Environment**: Compose files, service connectivity, volume mounts
- ✅ **Production Deployment**: Image optimization, security, runtime functionality
- ✅ **Scripts**: Build scripts, deployment scripts, flag handling

### Robust Assertions

- **File existence** checks
- **Exit code** validation
- **Image existence** verification
- **Container status** monitoring
- **String content** matching
- **Service connectivity** testing

### Cleanup and Safety

- **Automatic cleanup** of test artifacts
- **Safe test naming** to avoid conflicts
- **Docker system isolation** for CI/CD
- **Comprehensive error reporting**

## Test Design Philosophy

### Result-Based Testing

Tests verify actual outcomes rather than just command execution:

- Images are actually created and functional
- Containers actually start and serve requests
- Build artifacts are properly optimized
- Security configurations are correctly applied

### Thorough Coverage

Each test suite covers:

- **Happy path** scenarios (normal operation)
- **Error handling** (invalid inputs, missing files)
- **Edge cases** (parameter order, flag combinations)
- **Integration** (end-to-end workflows)

### Maintainable Structure

- **Modular design** - Each test suite is independent
- **Common utilities** - Shared functions in `common.sh`
- **Clear naming** - Tests are self-documenting
- **Isolated cleanup** - No test pollution between runs

## CI/CD Integration

The test suite is designed for automated environments:

```bash
# Pre-flight checks
- Docker availability
- Docker daemon status
- Correct working directory

# Parallel-safe
- Unique test naming
- Isolated test artifacts
- No shared state between tests

# Comprehensive reporting
- Individual test results
- Suite-level summaries
- Overall success/failure
- Detailed error messages
```

## Test Output

### Success Example

```
🧪 MTAV Docker Test Suite Runner
==================================

✅ Test suite 'scripts' PASSED
✅ Test suite 'build' PASSED
✅ Test suite 'mtav' PASSED
✅ Test suite 'dev' PASSED
✅ Test suite 'prod' PASSED

🏁 FINAL RESULTS
==================================
Total suites: 5
Passed: 5
Failed: 0

Success rate: 100%

🎉 ALL TESTS PASSED!
```

### Failure Example

```
❌ FAIL: PHP build should succeed
  Expected exit code: 0
  Actual exit code:   1

💥 Test suite 'build' FAILED

🏁 FINAL RESULTS
==================================
Total suites: 5
Passed: 4
Failed: 1

Success rate: 87%

💥 SOME TESTS FAILED
```

## Adding New Tests

To add new tests to existing suites:

1. **Use assertion functions** from `common.sh`
2. **Follow naming conventions** (descriptive test messages)
3. **Clean up test artifacts** (images, containers, files)
4. **Test both success and failure cases**

### Example Test Addition

```bash
# Test new functionality
log_info "Test N: New feature description"
run_command_capture output exit_code ./mtav new-command args
assert_exit_code 0 $exit_code "New command should succeed"
assert_contains "$output" "Expected text" "Should show expected output"
```

## Prerequisites

- Docker installed and running
- Bash shell environment
- MTAV project structure in place
- Sufficient disk space for test images

## Troubleshooting

### Common Issues

**"Docker daemon is not running"**

- Start Docker service/application

**"Tests must be run from MTAV project root"**

- Change to project root directory (where `mtav` script exists)

**Test cleanup failures**

- Manually clean up: `docker system prune -f`
- Check for running containers: `docker ps`

**Port conflicts during testing**

- Stop other Docker services using ports 8000, 5173, 3306
- Wait for dev environment cleanup to complete

This test suite provides confidence that all MTAV Docker functionality works correctly across different environments and use cases.
