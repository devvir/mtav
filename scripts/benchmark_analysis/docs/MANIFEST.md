# Copilot - Pending review
# Benchmark Analysis Tools - File Manifest

## Documentation Files

- **README.md** - Comprehensive documentation (installation, usage, API reference)
- **QUICKREF.md** - Quick reference guide with common commands
- **SUMMARY.md** - Overview, features, and use cases
- **THIS FILE** - Complete file listing and descriptions

## Core Python Modules

### benchmark_stats.py
Statistical analysis module.

**Key Classes**:
- `BenchmarkAnalyzer` - Main analysis class for single benchmark files

**Key Functions**:
- `compute_stats()` - Calculate comprehensive statistics
- `analyze_timeouts()` - Analyze timeout patterns
- `detect_outliers()` - Detect outliers using IQR or Z-score methods
- `get_summary_text()` - Generate formatted text summary
- `export_stats_json()` - Export statistics to JSON
- `export_timeouts_csv()` - Export timeout cases to CSV
- `compare_sizes()` - Compare statistics across multiple files

**Output Statistics**:
- Time: mean, median, std, min, max, IQR, CV
- Percentiles: P25, P50, P75, P90, P95, P99
- Status rates: success, timeout, failed, infeasible
- Counts: total runs, successes, timeouts, failures
- Distribution: skewness, kurtosis (if scipy available)

### benchmark_viz.py
Visualization module using matplotlib and seaborn.

**Key Classes**:
- `BenchmarkVisualizer` - Visualization generator

**Key Methods**:
- `plot_time_distribution()` - Histogram of execution times
- `plot_status_breakdown()` - Pie chart of status distribution
- `plot_time_series()` - Time series with rolling average
- `plot_scatter_time_vs_iteration()` - Scatter plot with outlier highlighting
- `plot_box_comparison()` - Box plot comparison across files
- `plot_percentile_comparison()` - Percentile comparison bar chart
- `generate_report()` - Generate comprehensive report with all visualizations

**Output Formats**:
- PNG images (300 DPI)
- Interactive plots (when show=True)
- Multiple plots in single report

### benchmark_compare.py
Multi-file comparison and prediction module.

**Key Classes**:
- `BenchmarkComparer` - Comparison and prediction engine

**Key Methods**:
- `compare_all()` - Compare statistics across all files
- `analyze_size_scaling()` - Analyze performance scaling with problem size
- `predict_time()` - Predict execution time for new problem size
- `plot_scaling_analysis()` - Visualize scaling behavior
- `compare_scenarios()` - Compare different scenarios at same size

**Capabilities**:
- Polynomial regression for time prediction
- Timeout rate extrapolation
- Scaling order determination (linear vs quadratic)
- Multi-metric comparison

### cli.py
Command-line interface using Click framework.

**Commands**:
- `analyze` - Analyze single file, display statistics
- `compare` - Compare multiple files, output table
- `report` - Generate comprehensive report with visualizations
- `timeouts` - Analyze timeout patterns
- `outliers` - Detect and analyze outliers
- `plot` - Generate individual plots

**Options**:
- Output formats: table, CSV, markdown, JSON
- Export capabilities for all analysis types
- Flexible filtering and formatting

### test_tools.py
Verification script to test installation.

**Tests**:
- Module imports
- Basic analysis functionality
- Visualization initialization
- File discovery

## Setup & Configuration

### requirements.txt
Python dependencies list.

**Key Packages**:
- pandas, numpy - Data manipulation
- matplotlib, seaborn - Visualization
- scipy - Advanced statistics
- click - CLI framework
- tabulate - Table formatting
- jupyter - Notebooks (optional)

### setup.sh
Automated setup script.

**Actions**:
1. Check Python version
2. Create virtual environment
3. Activate environment
4. Upgrade pip
5. Install requirements
6. Display usage instructions

### .gitignore
Git ignore patterns.

**Ignored**:
- Virtual environment (venv/)
- Python cache (__pycache__/, *.pyc)
- Jupyter checkpoints
- Output directories (reports/, output/)
- Generated files (*.png, *.csv, *.json except requirements.txt)
- IDE files (.vscode/, .idea/)

### __init__.py
Package initialization file.

**Exports**:
- `BenchmarkAnalyzer`
- `BenchmarkVisualizer`
- `compare_sizes`

## Usage Workflows

### Quick Analysis
```bash
python cli.py analyze file.csv
```
Uses: cli.py → benchmark_stats.py

### Comprehensive Report
```bash
python cli.py report file.csv -o reports/
```
Uses: cli.py → benchmark_viz.py → benchmark_stats.py

### Multi-File Comparison
```bash
python cli.py compare file1.csv file2.csv file3.csv
```
Uses: cli.py → benchmark_stats.py (compare_sizes)

### Scaling Prediction
```python
from benchmark_compare import BenchmarkComparer
comparer = BenchmarkComparer(['10.csv', '20.csv', '30.csv'])
prediction = comparer.predict_time(40, 'random')
```
Uses: benchmark_compare.py → benchmark_stats.py

## File Dependencies

```
cli.py
├── benchmark_stats.py (for analyze, compare)
├── benchmark_viz.py (for report, plot)
└── benchmark_compare.py (for advanced comparison)

benchmark_viz.py
└── benchmark_stats.py (uses BenchmarkAnalyzer)

benchmark_compare.py
└── benchmark_stats.py (uses BenchmarkAnalyzer)

test_tools.py
├── benchmark_stats.py
└── benchmark_viz.py
```

## Integration Points

### With MTAV System
- Reads CSV from: `storage/benchmarks/glpk_*.csv`
- Generates reports for: `documentation/ai/lottery/BENCHMARKS.md`
- Analyzes data from: `app/Console/Commands/BenchmarkGlpkSolver.php`

### With External Tools
- Jupyter notebooks for exploration
- Markdown for documentation
- JSON for data interchange
- PNG for publications/presentations

## Total File Count

- **Documentation**: 4 files (README, QUICKREF, SUMMARY, MANIFEST)
- **Python modules**: 4 files (stats, viz, compare, cli)
- **Examples**: 2 files (notebook, script)
- **Setup**: 4 files (requirements, setup.sh, .gitignore, __init__.py)
- **Tests**: 1 file (test_tools.py)

**Total**: 15 files

## Version History

- **v1.0.0** (2025-12-18) - Initial release
  - Core statistical analysis
  - Visualization suite
  - Multi-file comparison
  - CLI interface
  - Jupyter notebook examples
  - Comprehensive documentation

## Future Roadmap

Planned enhancements:
- Interactive dashboards (Plotly/Dash)
- Machine learning for timeout prediction
- Real-time monitoring integration
- Database storage for historical trends
- Web-based report viewer
- Automated anomaly detection
