# GLPK benchmark analysis

Statistics and charts of how long the lottery's solver takes, by instance size and kind
(the thesis's measurements).

1. Run the benchmarks in the app: `./mtav artisan benchmark:glpk --size=20 --scenario=realistic --iterations=100`
   (`--scenario` random, realistic or identical; `benchmark:glpk:retry` re-runs the
   failures). Each run writes `storage/benchmarks/glpk_<scenario>_<size>.csv`.
2. Analyse them all: `./mtav stats` (Python 3 with `requirements.txt`; `setup.sh` makes a
   virtual environment). Charts and summaries land in `output/`: per file, per size, and
   compared.

One file: `python cli.py analyze ../../storage/benchmarks/glpk_random_30.csv`
(`python cli.py --help` lists the rest).
