# Copilot - Pending review
# GLPK Benchmark Analysis - Quick Reference

## Installation

```bash
cd scripts/benchmark_analysis
./setup.sh
source venv/bin/activate
```

## Quick Commands

### Analyze Single File
```bash
# Basic stats
python cli.py analyze ../../storage/benchmarks/glpk_random_30.csv

# Export to JSON
python cli.py analyze file.csv --json stats.json
```

### Compare Multiple Files
```bash
# Compare all random scenarios
python cli.py compare ../../storage/benchmarks/glpk_random_*.csv

# Export as markdown table
python cli.py compare file1.csv file2.csv --format markdown -o comparison.md
```

### Generate Comprehensive Report
```bash
# Single file report
python cli.py report file.csv -o reports/analysis/

# With comparisons
python cli.py report file1.csv -o reports/ -c file2.csv -c file3.csv
```

### Timeout Analysis
```bash
# Show timeout details
python cli.py timeouts file.csv --details

# Export timeout data
python cli.py timeouts file.csv --export timeouts.csv
```

### Outlier Detection
```bash
# Detect outliers (IQR method)
python cli.py outliers file.csv --top 20

# Use Z-score method
python cli.py outliers file.csv --method zscore --threshold 3 --export outliers.csv
```

### Individual Plots
```bash
# Time distribution
python cli.py plot file.csv --plot-type distribution -o dist.png

# Time series
python cli.py plot file.csv --plot-type timeseries -o timeseries.png

# All plots
python cli.py plot file.csv --plot-type all
```

## Python API

### Basic Analysis
```python
from benchmark_stats import BenchmarkAnalyzer

analyzer = BenchmarkAnalyzer('benchmark.csv')
stats = analyzer.compute_stats()

print(f"Mean: {stats['time_mean']:.2f}ms")
print(f"Success rate: {stats['success_rate']:.2%}")

# Print formatted summary
print(analyzer.get_summary_text())
```

### Comparison
```python
from benchmark_stats import compare_sizes

files = ['glpk_random_10.csv', 'glpk_random_20.csv']
comparison = compare_sizes(files)
print(comparison)
```

### Visualization
```python
from benchmark_viz import BenchmarkVisualizer

viz = BenchmarkVisualizer('benchmark.csv')

# Single plots
viz.plot_time_distribution()
viz.plot_status_breakdown()
viz.plot_time_series()

# Generate all
viz.generate_report('output_dir/', other_files=['other.csv'])
```

### Scaling Analysis
```python
from benchmark_compare import BenchmarkComparer

files = ['glpk_random_10.csv', 'glpk_random_20.csv', 'glpk_random_30.csv']
comparer = BenchmarkComparer(files)

# Analyze scaling
scaling = comparer.analyze_size_scaling('random')
print(f"Sizes: {scaling['sizes']}")
print(f"Mean times: {scaling['mean_times']}")

# Predict for new size
prediction = comparer.predict_time(40, 'random')
print(f"Predicted time for 40x40: {prediction['predicted_mean_time']:.1f}ms")
```

### Outlier Detection
```python
from benchmark_stats import BenchmarkAnalyzer

analyzer = BenchmarkAnalyzer('benchmark.csv')

# IQR method
outliers = analyzer.detect_outliers(method='iqr', threshold=1.5)

# Z-score method
outliers = analyzer.detect_outliers(method='zscore', threshold=3)

print(f"Found {len(outliers)} outliers")
print(outliers.head())
```

## Available Statistics

From `analyzer.compute_stats()`:

- **Time stats**: `time_mean`, `time_median`, `time_std`, `time_min`, `time_max`
- **Percentiles**: `time_p25`, `time_p50`, `time_p75`, `time_p90`, `time_p95`, `time_p99`
- **Dispersion**: `time_iqr`, `time_cv` (coefficient of variation)
- **Status**: `success_rate`, `timeout_rate`, `failed_rate`, `infeasible_rate`
- **Counts**: `total_runs`, `success_count`, `timeout_count`, etc.
- **Distribution**: `time_skewness`, `time_kurtosis` (if scipy available)

## Common Workflows

### 1. Initial Analysis of New Benchmark
```bash
python cli.py analyze benchmark.csv
python cli.py timeouts benchmark.csv --details
python cli.py outliers benchmark.csv --top 10
```

### 2. Size Comparison
```bash
python cli.py compare storage/benchmarks/glpk_random_*.csv --format markdown
```

### 3. Full Report Generation
```bash
python cli.py report file.csv -o reports/$(date +%Y%m%d)/
```

### 4. Scaling Prediction
```python
from benchmark_compare import BenchmarkComparer

comparer = BenchmarkComparer(['10.csv', '20.csv', '30.csv'])
pred = comparer.predict_time(40, 'random')
print(f"40x40 prediction: {pred['predicted_mean_time']:.1f}ms")
```

# Quick test
python test_tools.py
```

## Troubleshooting

### Import Errors
```bash
pip install -r requirements.txt
```

### Matplotlib Backend Issues
Add to top of script:
```python
import matplotlib
matplotlib.use('Agg')  # For non-GUI environments
```

### Memory Issues with Large Files
Process in chunks or use `--export` to save intermediate results.

## File Locations

- **Benchmarks**: `storage/benchmarks/`
- **Tools**: `scripts/benchmark_analysis/`
- **Reports**: Generated where you specify with `-o`
- **Docs**: `documentation/ai/lottery/BENCHMARKS.md`
