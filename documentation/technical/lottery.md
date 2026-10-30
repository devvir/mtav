# The lottery

How a project's lottery runs, from the families' rankings to a result anyone in the
project can check. The mathematics (the two phases, the binary search) are in the
thesis, part 3; this guide follows the code.

## Rankings

Each family ranks the units of its type, most wanted first (`PreferencesService`). Any
member of the family changes the one ranking it has; every change is saved at once.
A family may rank only some units, or none: the rest follow in an order fixed per family
(a hash of the app's key, the family and the unit), random-looking but always the same,
never written down. Rankings are locked while a run is running or completed.

## Running it

Once the lottery's date has come, an admin runs it (`LotteryService::run`):

1. In one transaction, with the project locked, it checks the lottery may run: it is
   dated and due, no run is active, a queue worker is alive, there are at least two
   families (and, in a limited project, not too many), and where a type has more units
   than families, or fewer, the admin confirmed it. Then a `lottery_runs` row is written
   (`running`), and the **manifest** is recorded: every family's whole ranking, per unit
   type, as it is now.
2. `RunLottery` (queued) solves it. Workers say they are alive as they loop
   (`Services/Queue/Workers`); without one, the page says the lottery is held.
3. The solver's assignment is **held** until the run's `reveal_at`: everyone watches the
   lottery work for at least the time the platform's settings say (superadmins set it;
   20 seconds by default). `PublishLotteryRun` writes it then, all units at once, in one
   transaction (`completed`). The time changes when the result shows, never what it is.
4. Everyone is notified. A run that fails is closed as `failed`, its admin is told, and
   the lottery can run again.

## Solving

`LotteryOrchestrator` solves each unit type on its own, then one last round for whatever
was left over (families of types with too few units, units of types with too many),
where every leftover unit is equally good for every leftover family.

The solver is GLPK (`Solvers/GlpkSolver`, `Solvers/Glpk/`), run as `glpsol` on models
written for each solve (`ModelGenerator`, `DataGenerator`):

- **Phase 1** makes the worst-off family as well off as possible: the best rank the
  worst-placed family can get. Large instances use a binary search over that rank
  (`HybridDistribution`), which finds the same answer as solving it directly.
- **Phase 2** keeps that bound and makes everyone as satisfied as possible together.
- **Ties** (several equally good assignments) are broken by a tiny random term on each
  rank, so the lottery is a lottery only where the rankings leave it open.
- **Uneven counts**: with more units than families, the least wanted units are set
  aside first (`WorstUnitsPruning`); with more families, placeholder units balance it.

The solvers work on plain data (`LotteryManifest`, `LotterySpec`), never on models.
`config/lottery.php` names the solver (`glpk`; `random` and `test` exist for development)
and GLPK's time limits.

## The record

Every step is written to `lottery_audits` as it happens and never changed: the
manifest (`init`), each type's result (`group_execution`), the whole result
(`project_execution`), a failure, an invalidation. It lasts as long as the project.

From it, `RunRecord` gives each run's **record page**, for anyone in the project: every
family with the unit it received and that unit's place in the family's ranking as
recorded ("2nd of 12"), the worst place and the average, and the record's
**fingerprint** (SHA-256 of the manifest and the result, as stored). Families see their
own whole ranking there; nobody sees another family's. Admins download the whole trail
as JSON, to keep or hand to an auditor.

## Undoing a run

Only exceptionally: an admin invalidates a completed run, giving a reason. Every unit is
freed, the run becomes `invalidated` (who, when, why: shown to everyone in the project,
and notified), and the lottery can run again, as a new run with its own record.
