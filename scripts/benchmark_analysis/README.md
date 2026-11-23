# GLPK benchmark analysis

Statistics and charts of how long the lottery's solver takes, by instance size and kind
(the thesis's measurements).

1. Run the benchmarks in the app: `./mtav artisan benchmark:glpk --size=20 --scenario=realistic --iterations=100`
   (`--scenario` random, realistic, opposite or identical; `benchmark:glpk:retry` re-runs the
   failures). Each run writes `storage/benchmarks/glpk_<scenario>_<size>.csv`. With `--original`
   Phase 1 is solved by GLPK directly, the method the thesis compares against, and the file goes
   to `storage/benchmarks/original/`.
2. Analyse them all: `./mtav stats` (Python 3 with `requirements.txt`; `setup.sh` makes a
   virtual environment). Charts and summaries land in `output/`: per file, per size, and
   compared.
3. The thesis's two figures and its tables: `python3 thesis_figures.py --tables` (writes the
   PNGs into `documentation/thesis/assets/`).

The thesis's series (November 2026, one machine, the code as it is): 1 000 runs per size up to
100 (1 000 at 150 too for random), 100 runs at 150–500, for the four scenarios; 10 000 runs per
size at 5, 10, 20, 25 and 30 for the original method, timeout 30 s.

One file: `python cli.py analyze ../../storage/benchmarks/glpk_random_30.csv`
(`python cli.py --help` lists the rest).
