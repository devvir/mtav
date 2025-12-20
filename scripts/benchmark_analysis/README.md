# GLPK Benchmark Analysis Toolkit

Python toolkit for analyzing GLPK lottery solver benchmark data with comprehensive statistics, visualizations, and predictions.

## Quick Start - Analyze ALL Benchmarks

**Most used command**: Generate analysis for ALL existing benchmark CSV files at once:

```bash
# Simple command (runs from project root)
mtav stats

# Or manually:
cd scripts/benchmark_analysis
python3 generate_all.py
```

This generates:
- Individual statistics and visualizations for each benchmark file
- Cross-benchmark comparisons and scaling analysis
- Output in `output/` directory

## Analyzing Single Benchmarks

```bash
# One-time setup
./setup.sh
source venv/bin/activate

# Generate full report with visualizations for a single file
python cli.py report ../../storage/benchmarks/glpk_random_30.csv -o my_report/

# View the generated PNG images in my_report/
```

## What You Get with `mtav stats`

- **5 visualizations per benchmark** (time_distribution, status, time_series, scatter, etc.)
- **5 comparison visualizations per scenario** (box plots, log-scale, percentiles, etc.)
- **Statistical summaries** (text + JSON, organized by scenario)
- **Outlier detection** and timeout analysis
- **Multi-file comparison** and size scaling predictions
- **300 DPI PNG images** suitable for documentation

## Documentation

### Getting Started
- **QUICKREF.md** - Common commands and quick examples
- **setup.sh** - Run this first to install dependencies

### Understanding the Output
- **VISUAL_GUIDE.md** - What each chart shows and how to read it
- **STATISTICS_EXPLAINED.md** - Plain English explanations of IQR, CV, percentiles, etc.

### Reference
- **MANIFEST.md** - Complete file listing and module descriptions

## Common Commands

```bash
# Analyze single file
python cli.py analyze file.csv

# Compare multiple sizes
python cli.py compare file1.csv file2.csv file3.csv

# Generate comprehensive report
python cli.py report file.csv -o output_dir/

# Investigate timeouts
python cli.py timeouts file.csv --details

# Find outliers
python cli.py outliers file.csv --top 20
```

## What the Visualizations Show

### Individual Benchmark Analysis (one folder per CSV file)
When you run `python cli.py report`, you get:

1. **time_distribution.png** - How execution times are spread (histogram)
2. **time_distribution_log.png** - Same but with log scale (shows outliers better)
3. **status_breakdown.png** - Success vs timeout vs failed (pie chart)
4. **time_series.png** - Performance over time with rolling average
5. **scatter_outliers.png** - Every run plotted, outliers highlighted

Plus `statistics.txt` and `statistics.json` with all the numbers.

### Scenario Comparison Charts (grouped by scenario type)
When you run `mtav stats`, comparison charts are generated per scenario:

6. **box_comparison.png** - Box plots comparing all sizes for one scenario
7. **box_comparison_log.png** - Same with log scale (better for small runs)
8. **percentile_comparison.png** - P50/P75/P90/P95/P99 across sizes
9. **percentile_comparison_log.png** - Same with log scale

Plus `size_comparison.txt/json` with detailed statistics by size.

See **VISUAL_GUIDE.md** for detailed explanations of each chart.

## Key Statistics Explained

Not a stats expert? See **STATISTICS_EXPLAINED.md** for plain English explanations of:

- **IQR** (Interquartile Range) - The middle 50% spread, used for outlier detection
- **CV** (Coefficient of Variation) - Variability as a %, lets you compare consistency across sizes
- **Percentiles** (P50, P90, P95, P99) - Where the slow runs fall, critical for user experience
- **Outliers** - Runs that are abnormally slow, need investigation

Quick interpretation:
- **CV < 20%** = Good consistency
- **CV > 50%** = Unpredictable performance
- **P99 > 5× P50** = Severe tail latency problem

## Python API

```python
from benchmark_stats import BenchmarkAnalyzer
from benchmark_viz import BenchmarkVisualizer
from benchmark_compare import BenchmarkComparer

# Analyze single file
analyzer = BenchmarkAnalyzer('benchmark.csv')
print(analyzer.get_summary_text())
stats = analyzer.compute_stats()

# Generate visualizations
viz = BenchmarkVisualizer('benchmark.csv')
viz.plot_time_distribution()
viz.generate_report('output_dir/')

# Compare and predict
comparer = BenchmarkComparer(['10.csv', '20.csv', '30.csv'])
prediction = comparer.predict_time(40, 'random')
```

## File Structure

```
benchmark_analysis/
├── README.md                    # This file
├── QUICKREF.md                  # Quick command reference
├── VISUAL_GUIDE.md              # How to read the charts
├── STATISTICS_EXPLAINED.md      # Stats concepts in plain English
├── MANIFEST.md                  # Complete file listing
│
├── cli.py                       # Command-line interface
├── benchmark_stats.py           # Statistical analysis
├── benchmark_viz.py             # Visualization generation
├── benchmark_compare.py         # Multi-file comparison
│
├── test_tools.py                # Verification tests
│
├── requirements.txt             # Python dependencies
└── setup.sh                     # Installation script
```

## Understanding the Output Structure

### After running `mtav stats`:

```
output/
├── glpk_random_5/           # Individual analysis (5x5 size, random scenario)
│   ├── statistics.json/txt
│   ├── time_distribution.png
│   ├── time_distribution_log.png
│   ├── status_breakdown.png
│   ├── time_series.png
│   ├── scatter_outliers.png
│   └── outliers.json (if found)
│
├── glpk_random_10/          # Individual analysis (10x10 size, random scenario)
├── glpk_random_20/
├── ... (one per benchmark CSV)
│
└── comparisons/             # Grouped by scenario
    ├── random/              # All random scenario sizes compared
    │   ├── size_comparison.json/txt
    │   ├── box_comparison.png
    │   ├── box_comparison_log.png      (better for small sizes)
    │   ├── percentile_comparison.png
    │   ├── percentile_comparison_log.png (better for visibility)
    │   └── scaling_analysis.json/txt
    │
    ├── identical/           # All identical scenario sizes
    ├── opposite/            # All opposite scenario sizes
    └── realistic/           # All realistic scenario sizes
```

## Workflow Examples

### Generate All Analysis at Once (Most Common)
```bash
mtav stats
# Generates everything: individual + scenario comparisons
# Results in output/ (automatically organized by scenario)
```

### Analyze Single Benchmark
```bash
python cli.py report benchmark.csv -o reports/single/
# For quick analysis of one file only
```

### Investigating Performance Issues
```bash
# Get the numbers for one file
python cli.py analyze benchmark.csv

# Find outliers
python cli.py outliers benchmark.csv --top 10 --export outliers.csv

# Check for timeouts
python cli.py timeouts benchmark.csv --details
```

## Requirements

- Python 3.7+
- pandas, numpy, matplotlib, seaborn, scipy, click, tabulate
- Optional: jupyter (for interactive notebook)

All dependencies install automatically with `./setup.sh`

## Integration with MTAV

This toolkit analyzes CSV files from:
- `../../storage/benchmarks/glpk_*.csv` (benchmark data)
- Generated by: `app/Console/Commands/BenchmarkGlpkSolver.php`

## Tips

1. **Start with the pie chart** (`status_breakdown.png`) - shows success rate
2. **Check the histogram** (`time_distribution.png`) - shows typical performance
3. **Look for heavy tails** in the log scale histogram
4. **Compare box plots** across sizes to see how IQR grows
5. **Watch P99** - that's what unlucky users experience

## Need Help?

- **Common commands**: See QUICKREF.md
- **Chart interpretation**: See VISUAL_GUIDE.md
- **Stats concepts**: See STATISTICS_EXPLAINED.md
- **Test setup**: Run `python test_tools.py`

## Version

v2.0.0 (December 2025)
- Added scenario-based grouping for comparisons
- Added logarithmic scale versions of comparison charts
- Integrated with `mtav stats` command for one-command analysis
