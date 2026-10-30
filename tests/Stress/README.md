# Stress tests

The lottery's solver on hard instances: slow (minutes), so they run only on demand.

```sh
./mtav pest --testsuite Stress
```

| Test | Checks |
|---|---|
| `Lottery/Glpk/GlpkTimeoutTest.php` | large random instances finish within GLPK's limits; an impossible bound answers "infeasible", never a wrong assignment |
| `Lottery/Glpk/BinarySearchEquivalenceTest.php` | the binary search finds exactly the worst-off family's rank that solving it directly finds, on the instances that used to be slowest (`tests/Datasets/BinarySearchEquivalenceDataset.php`) |

Run them after touching anything in `app/Services/Lottery/Solvers`.
