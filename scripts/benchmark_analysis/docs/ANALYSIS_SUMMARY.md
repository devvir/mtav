# Benchmark Analysis - Generated Artifacts

**Location:** `scripts/benchmark_analysis/output/`

## Quick Start

Run this single command to generate everything:
```bash
cd scripts/benchmark_analysis
python3 generate_all.py
```

This will process all benchmark CSVs found in `storage/benchmarks/` and generate comprehensive analysis.

## Generated Structure

```
output/
├── glpk_random_5/
├── glpk_random_10/
├── glpk_random_20/
├── glpk_random_30/
├── glpk_random_40/
├── glpk_random_50/
├── glpk_random_75/
├── glpk_random_100/
└── comparisons/
```

## Individual Benchmark Folders

Each `glpk_random_X/` folder contains:

### Statistics Files
- **statistics.json** - All metrics in JSON format
- **statistics.txt** - Human-readable summary with:
  - Mean, median, std dev execution times
  - Percentiles (P25, P50, P75, P95, P99)
  - IQR (Interquartile Range)
  - CV (Coefficient of Variation)
  - Success/timeout rates
  - Skewness & kurtosis

### Analysis Files
- **outliers.json** - Detected outlier iterations (if any)
- **timeout_analysis.json** - Timeout patterns (if any timeouts occurred)

### Visualizations (PNG, 300 DPI)
1. **time_distribution.png** - Histogram of execution times
2. **time_distribution_log.png** - Same but with log scale
3. **status_breakdown.png** - Pie chart of SUCCESS/TIMEOUT/ERROR
4. **time_series.png** - Execution time over iterations
5. **scatter_outliers.png** - Scatter plot with outliers highlighted

## Comparisons Folder

Cross-benchmark analysis files:

### Comparison Files
- **size_comparison.json** - Stats for all sizes in JSON
- **size_comparison.txt** - Human-readable comparison table

### Comparison Visualizations
- **box_comparison.png** - Box plots comparing all sizes
- **percentile_comparison.png** - Percentile bars across sizes

### Scaling Analysis
- **scaling_analysis.json** - Polynomial fit coefficients
- **scaling_analysis.txt** - Scaling behavior description
  - Quadratic vs linear scaling
  - Polynomial coefficients
  - Observed data points

## Usage Examples

### View stats for 100x100 benchmark:
```bash
cat output/glpk_random_100/statistics.txt
```

### View cross-size comparison:
```bash
cat output/comparisons/size_comparison.txt
```

### Open visualizations:
```bash
xdg-open output/glpk_random_100/time_distribution.png
xdg-open output/comparisons/box_comparison.png
```

### Check outliers:
```bash
cat output/glpk_random_100/outliers.json
```

## Processing Time

- Per file: ~2-5 seconds (depends on CSV size)
- Total for 8 files: ~30-60 seconds
- Output size: ~10-15 MB (all visualizations + data)

## Notes

- All visualizations use non-interactive matplotlib backend (no display required)
- PNG files are high-resolution (300 DPI) suitable for thesis/papers
- JSON files are machine-readable for further processing
- TXT files are human-readable summaries
- Script automatically detects all `glpk_*.csv` files in benchmarks directory
- Safe to re-run - will regenerate all artifacts

## Integration with Existing Tools

This `generate_all.py` script uses the same underlying modules as the CLI tools:
- `benchmark_stats.py` - Statistical analysis
- `benchmark_viz.py` - Visualization generation
- `benchmark_compare.py` - Cross-benchmark comparison

You can still use individual CLI commands if you need specific outputs:
```bash
python3 cli.py analyze <file>.csv
python3 cli.py compare <file1>.csv <file2>.csv
python3 cli.py report <file>.csv -o output_dir/
```

See `INDEX.md` for full documentation.
