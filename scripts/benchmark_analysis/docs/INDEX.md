# Benchmark Analysis Toolkit - Documentation Index

**Quick navigation to all documentation**

## I'm New Here - Where Do I Start?

1. **README.md** ← Start here! Overview and quick start
2. Run `./setup.sh` to install
3. Run `python cli.py report ../../storage/benchmarks/glpk_random_30.csv -o my_report/`
4. Open the PNG images in `my_report/` folder

## I Want To...

### Run Analysis
- **Quick commands** → QUICKREF.md
- **First time setup** → README.md (Quick Start section)
- **Command examples** → examples.py or run `python cli.py --help`

### Understand the Output
- **What do the charts show?** → VISUAL_GUIDE.md
- **What is IQR, CV, percentiles?** → STATISTICS_EXPLAINED.md
- **Interpret my results** → VISUAL_GUIDE.md (Interpretation sections)

### Use as Developer
- **Python API reference** → MANIFEST.md
- **Module documentation** → Docstrings in .py files

### Learn More
- **What files exist?** → MANIFEST.md
- **Technical details** → README.md and module docstrings

## Documentation Files

### User Guides
| File | Purpose | When to Use |
|------|---------|-------------|
| **README.md** | Main documentation, quick start | First time using the toolkit |
| **QUICKREF.md** | Command cheat sheet | Need a specific command quickly |
| **VISUAL_GUIDE.md** | Chart explanations | Understanding generated visualizations |
| **STATISTICS_EXPLAINED.md** | Stats concepts in plain English | "What does IQR mean?" |

### Reference
| File | Purpose | When to Use |
|------|---------|-------------|
| **MANIFEST.md** | Complete file listing | Understanding the codebase |
| **INDEX.md** | This file! Navigation hub | Finding the right doc |

### Examples
| File | Purpose | When to Use |
|------|---------|-------------|
| **test_tools.py** | Verification script | Test installation |

### Setup
| File | Purpose | When to Use |
|------|---------|-------------|
| **setup.sh** | Installation script | First time setup |
| **requirements.txt** | Python dependencies | Manual pip install |

## Reading Order for New Users

1. **README.md** - Get the overview
2. **STATISTICS_EXPLAINED.md** - Understand what CV, IQR mean
3. **QUICKREF.md** - Learn the commands
4. Run: `python cli.py report <your_file>.csv -o output/`
5. **VISUAL_GUIDE.md** - Interpret your charts

## Common Questions

**Q: How do I generate charts?**
A: `python cli.py report file.csv -o output_dir/`
Details: QUICKREF.md

**Q: What does this chart mean?**
A: VISUAL_GUIDE.md has explanations for all 7 chart types

**Q: What is IQR? CV? P99?**
A: STATISTICS_EXPLAINED.md explains all statistical terms

**Q: How do I compare multiple sizes?**
A: `python cli.py report file1.csv -o output/ -c file2.csv -c file3.csv`
Details: QUICKREF.md or README.md

**Q: Can I use this from Python code?**
A: Yes! See examples.py or the Python API section in README.md

**Q: Where are my visualizations?**
A: They're PNG files in the output directory you specified with `-o`

## File Organization

```
benchmark_analysis/
│
├── INDEX.md                     ← You are here
├── README.md                    ← Start here
│
├── Documentation/
│   ├── QUICKREF.md              ← Command reference
│   ├── VISUAL_GUIDE.md          ← Chart explanations
│   ├── STATISTICS_EXPLAINED.md  ← Stats concepts
│   └── MANIFEST.md              ← Technical reference
│
├── Code/
│   ├── cli.py                   ← Main interface
│   ├── benchmark_stats.py       ← Statistics engine
│   ├── benchmark_viz.py         ← Visualization generator
│   └── benchmark_compare.py     ← Comparison & prediction
│
├── Examples/
│   └── test_tools.py            ← Tests
│
└── Setup/
    ├── setup.sh                 ← Installation
    └── requirements.txt         ← Dependencies
```

## Getting Help

1. **Command help**: `python cli.py --help` or `python cli.py <command> --help`
2. **Test setup**: `python test_tools.py`
3. **See examples**: `python examples.py`
4. **Check docs**: Start with README.md

## External References

- Main project docs: `../../documentation/ai/lottery/BENCHMARKS.md`
- Benchmark data: `../../storage/benchmarks/`
- Data generator: `../../app/Console/Commands/BenchmarkGlpkSolver.php`

---

**TL;DR**: Run `./setup.sh`, then `python cli.py report <file>.csv -o output/`, then look at the PNG images!
