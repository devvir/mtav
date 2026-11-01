# Stress tests

The lottery's solver on hard instances: too slow for every run (over a minute), so they run on
demand, and in GitHub on every push that touches the solvers (`.github/workflows/solver.yml`).

```sh
./mtav pest --testsuite Stress
```

| Test | Checks |
|---|---|
| `Lottery/Glpk/GlpkTimeoutTest.php` | large random instances finish within GLPK's limits; an impossible bound answers "infeasible", never a wrong assignment |
| `Lottery/Glpk/BinarySearchEquivalenceTest.php` | the binary search finds exactly the worst-off family's rank that solving it directly finds, on the instances that used to be slowest (`tests/Datasets/BinarySearchEquivalenceDataset.php`) |
