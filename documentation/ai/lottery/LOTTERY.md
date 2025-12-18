<!-- Copilot - Pending review -->

# Lottery System Reference

## Purpose

One-time, fair housing unit assignment for cooperative projects using mathematical optimization (GLPK) to maximize overall satisfaction while ensuring max-min fairness.

## Core Principles

**One-Time & Immutable**: Binary state (pending → executed), runs once per project, permanent assignments, all units assigned simultaneously.

**Preference-Based**: Families rank ALL units of their type, preferences locked when execution starts, no changes after execution.

**Type Segregation**: Each family assigned to ONE unit type, can only prefer units of their type, algorithm runs independently per type, orphan redistribution handles cross-type.

**Global Optimization**: Mathematical optimization (not random/FCFS), two-phase GLPK (max-min fairness + overall satisfaction), considers all preferences simultaneously.

## Key Data Structures

- `unit_preferences` pivot: family_id, unit_id, order (1=top choice)
- `Unit.family_id`: NULL before lottery, set after (source of truth for assignments)
- `Event` with `type=LOTTERY`: tracks lottery state via `is_published` and `deleted_at`

### Lottery State
- **Ready**: `is_published=true`, `deleted_at=NULL`
- **Executing**: `is_published=false`, `deleted_at=NULL` (locked)
- **Complete**: `is_published=false`, `deleted_at≠NULL`

## Architecture

### Layers
1. **HTTP**: Controllers, validation, authorization
2. **Service**: `LotteryService`, `ExecutionService`, `ConsistencyService`
3. **Orchestration**: Two-phase execution (by type → orphan redistribution)
4. **Solver Strategy**: Pluggable solvers (Random, Test, GLPK)
5. **Persistence**: Audit trail, assignment application

### Execution Flow
```
User triggers → ExecutionService validates & reserves (is_published=false)
→ Dispatches LotteryExecution (queued)
→ ExecuteLotteryListener resolves solver from config
→ LotteryOrchestrator runs two-phase:
    Phase 1: Execute per unit type, collect orphans
    Phase 2: Redistribute orphans cross-type
→ Dispatches GroupLotteryExecuted per group
→ Dispatches ProjectLotteryExecuted on completion
→ Creates audit records, updates assignments, soft-deletes lottery
```

### Dynamic Preferences

**Problem**: Housing data is fluid (families change types, units added/removed).

**Solution**: Runtime resolution via `LotteryService::preferences(family)`
1. Sanitize (remove invalid preferences)
2. Load explicit preferences from pivot
3. Auto-fill remaining units (sorted by ID)
4. Return complete list

**Benefits**: Always valid, no manual cleanup, new units auto-included.

## GLPK Solver

**Status**: ✅ Production-ready with binary search fallback

**Overview**: Mathematical optimization using GLPK (GNU Linear Programming Kit) for provably optimal, fair housing assignments.

**Quick Facts**:
- Two-phase optimization (max-min fairness + overall satisfaction)
- Typical execution: 2-5 seconds for 20-100 entities
- Binary search fallback for degenerate cases
- Deterministic results (same input = same output)

**For Details**: See **`GLPK.md`** for comprehensive technical documentation including:
- Mathematical formulation
- Architecture & execution flow
- Binary search fallback algorithm
- Error handling & recovery
- Performance benchmarks
- Testing strategy

**Configuration**: See `config/lottery.php` for solver settings.

## Audit Trail

### Types (LotteryAuditType)
- **INIT**: Execution start, stores complete manifest
- **GROUP_EXECUTION**: One per unit-type group
- **PROJECT_EXECUTION**: Final completion marker
- **INVALIDATE**: Lottery reversal by superadmin
- **FAILURE**: Execution error with details

### UUID Grouping
All audits from single execution share same UUID, enables tracing complete flow, supports multiple runs per lottery.

## Authorization

**Superadmins**: Can invalidate results (exceptional cases)
**Admins**: Configure lottery, execute, view all preferences
**Members**: Update own family preferences, view results

## Testing

Uses `universe.sql` fixture (20-30x faster than factories). Test categories:
- Preference management & validation
- Execution validation & locking
- End-to-end execution with solvers
- Audit trail & persistence
- UI health checks

See `documentation/ai/testing/LOTTERY_TESTS.md` for complete reference.

## Known Issues & Next Steps

### Investigation Items

1. **Deferred Event Handling** - Investigate if nested deferred events work correctly (sync fixed it, need to debug async version)

2. **executeMoreUnits Balance** - Families seem to send all preferences even when worst units removed (should trim to match selected units)

3. **Default Preference Bias** - Auto-fill sorts by unit ID, creating implicit bias. Need persistent randomization per family.

## Related Documentation

- `BENCHMARKS.md` - Performance analysis and predictive metrics
- `documentation/ai/testing/LOTTERY_TESTS.md` - Complete testing reference
- `documentation/ai/KNOWLEDGE_BASE.md` - Business domain overview
- `resources/js/components/lottery/README.md` - Frontend details
