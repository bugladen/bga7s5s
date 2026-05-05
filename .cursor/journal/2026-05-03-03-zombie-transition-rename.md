# Zombie Transition Rename — `""` → `"zombie"`

## Context

A previous refactor step (in 2026-05-03-02 likely) replaced `back` transitions
on a list of action initial states with a default unnamed (`""`) transition
pointing to `HIGH_DRAMA_PLAYER_TURN_EVENTS`, intended to support zombie
turn handling.

BGA framework rule: a state with multiple transitions cannot use the
unnamed (`""`) transition name. So the unnamed transition had to be renamed
to a proper name. We chose `"zombie"`.

## What I did

For every state that had MORE THAN ONE transition (i.e. `""` plus at least one
named action transition like `locationChosen`):

1. Renamed `"" => HIGH_DRAMA_PLAYER_TURN_EVENTS` to
   `"zombie" => HIGH_DRAMA_PLAYER_TURN_EVENTS`.
2. Updated the corresponding zombie path (either the `zombie()` method on the
   class-based State file, or a case in `ZombieTrait::doZombieTurn`) to call
   `nextState("zombie")` instead of `nextState()`.

States with only a single `""` transition were left as-is — those are
single-transition states where `""` is legitimate.

## Files modified

### Pattern A (class-based State files in `modules/php/States/_7s5s/` and `modules/php/States/tac/`)

Renamed `""` to `"zombie"` in `transitions:` AND updated `zombie()` method to
call `nextState("zombie")`:

- `_7s5s/`: 01007, 01041, 01064, 01091, 01092 (originally listed as Pattern B
  but actually has its own State_ file), 01093, 01096, 01097, 01102, 01117,
  01118, 01123, 01124, 01158
- `tac/`: 02001, 02002, 02013, 02023, 02025, 02033, 02034, 02036

Pattern A IDs that had no State_ file (skipped as Pattern A but were instead
inline-defined and treated as Pattern B): 01012, 01017, 01019, 01020, 01046a,
01049, 01068, 01069. 01071 doesn't exist anywhere.

### Pattern B (inline state defs in `states.7s5s.php`)

Renamed `""` to `"zombie"`: 01012, 01017, 01019, 01020, 01046a, 01049, 01068,
01069 (these were originally listed as Pattern A but had no State_ files),
01194, 01197, 01205.

01154 has only `""` as its single transition — left alone.

The other Pattern B IDs the user asked me to inspect (01009, 01046b, 01143,
01206, 02035) don't exist in the codebase at all.

Audit-list IDs (01011, 01015, 01044, 01147, 01148, 01149, 01152a, 01152b,
01156) were skipped as instructed. Note: contrary to the user's prediction,
several of these DO have `""` transitions in their inline defs (e.g. 01011,
01147, 01148, 01149, 01152a, 01152b, 01156). I left them alone per the
explicit instruction to skip the audit list — but that means they're now
in an inconsistent state vs. the rest of the codebase. Future work may
need to address them.

### `ZombieTrait.php`

Split out the renamed inline-def state names (01012, 01017, 01019, 01020,
01046a, 01049, 01068, 01069) into their own case block calling
`nextState("zombie")`. The other states in the original `nextState()` block
(audit-list ones plus a bunch of unrelated ones like 01024, 01025, etc.)
remain in the plain `nextState()` block — those still legitimately use `""`.

## WHY

The BGA framework rule: when a state has multiple named transitions, the
unnamed transition `""` cannot coexist with them. The framework needs an
explicit name to disambiguate. So the `"zombie"` name is purely a label
that distinguishes the auto-fallback transition (used by zombie turn
handler) from the action-driven named transitions.

I chose `"zombie"` because the only caller is `nextState("zombie")` in the
zombie() handler — that's what the transition is for.

## Anomalies / observations

- Several IDs in the user's list (01009, 01046b, 01071, 01143, 01206, 02035)
  don't exist in the codebase. Possibly typos or planned features.
- 01092 was listed as Pattern B but actually has a State_ file. Treated as
  Pattern A (renamed both transition and `zombie()` body).
- 01012, 01017, 01019, 01020, 01046a, 01049, 01068, 01069 were listed as
  Pattern A but have NO State_ files; they're inline-defined. Treated as
  Pattern B.
- The audit-list states (01011, 01147, 01148, 01149, 01152a, 01152b, 01156)
  DO have `""` transitions in their inline defs even though the user said
  they shouldn't. Skipped per instruction, but worth flagging for follow-up.
- 01154 has only one transition (`""`) — kept as `""` since it doesn't
  conflict with the BGA rule. Its `zombie()` method calls
  `actFromCardWithId(0)` rather than `nextState()`, so no zombie path
  update needed.
