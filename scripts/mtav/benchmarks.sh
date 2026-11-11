# shellcheck shell=bash
# Statistics and charts of the GLPK benchmarks (scripts/benchmark_analysis/README.md).

stats() {
    info "📊 Analyzing all benchmark files..."
    (cd scripts/benchmark_analysis && python3 generate_all.py)
}
