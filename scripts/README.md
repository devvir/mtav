<!-- Copilot - Pending review -->
# MTAV Utility Scripts

Collection of utility scripts for development, testing, and analysis.

## Available Tools

### benchmark_analysis/
**Python toolkit for GLPK lottery solver benchmark analysis**

Comprehensive statistical analysis and visualization tools for benchmark CSV files.

**Features**:
- Statistical analysis (mean, median, std dev, percentiles, outlier detection)
- Visualizations (histograms, time series, scatter plots, comparisons)
- Multi-file comparison and size scaling analysis
- Performance prediction using polynomial regression
- Timeout pattern analysis
- CLI and Python API

**Quick Start**:
```bash
cd benchmark_analysis
./setup.sh
source venv/bin/activate
python cli.py analyze ../../storage/benchmarks/glpk_random_30.csv
```

**Documentation**: See `benchmark_analysis/README.md`

### extract_snap_pngs.py
**Extract PNG images from Pest snapshot files**

Scans `tests/.pest/snapshots` for `.snap` files and extracts embedded PNG images to `tests/Browser/Snapshots/baseline/` for manual review.

**Usage**:
```bash
./scripts/extract_snap_pngs.py
```

## Future Tools

Additional utilities can be added here for:
- Database migrations and seeding
- Data export/import
- Performance profiling
- Code generation
- Deployment automation

