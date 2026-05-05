# Bulk refactor: remove redundant calls + back buttons after CONFIRM state

User asked me to do the mechanical follow-up to the new `HIGH_DRAMA_IN_PLAY_ACTION_CONFIRM` state (added in note 2026-05-03-01). Three calls (`announceAction`, `setUsed`, `resetPlayerPassCount`) are now done centrally, and the action's initial state can no longer be backed out of (it has already been announced). 70 action subclasses + their initial states needed updating.

## What I removed

For each of 70 action files matching `extends CharacterAction|AttachmentAction|SchemeAction|SchemeCityAction`:
- Every `$this->setUsed($..., true)` (kept `false` ones — those reset for end-of-day)
- Every `$this->resetPlayerPassCount($..)`
- Every `$this->announceAction($..)`
- Stale comment placeholders mentioning these methods (e.g., "is called in stSetupChallenge")

For each action's initial state (matching the action ID exactly, no `_2`/`_3` suffix):
- Removed the `back` transition (whether → CHOOSE_ACTION or → CHOOSE_PERFORMER)
- Added `"" => HIGH_DRAMA_PLAYER_TURN_EVENTS` as default transition (for zombie)
- For Pattern A (dedicated `State_*.php` classes): removed `actBack()` method entirely; changed `zombie()` body to `$this->game->gamestate->nextState();`
- For Pattern B (inline defs in `states.7s5s.php`): removed `"actBack"` from `possibleactions`

For ZombieTrait: added `highDramaInPlayActionConfirm` to the `actBack()` group (its back IS valid — it goes back to CHOOSE_ACTION before announcement). Moved 14 inline state names that lost their backs from the `actBack()` group to the `gamestate->nextState()` group.

## Why preserve `""` transition + zombie nextState behavior

The old back-on-zombie was a valid escape hatch: if a player went zombie mid-state, the game would back out and the next active player could try again. That no longer makes sense after CONFIRM (action committed, events fired). The new zombie behavior is: just advance to PLAYER_TURN_EVENTS, letting the action's `actFromAction*` skip and the dispatch carry forward. The `"" => EVENTS` transition lets `nextState()` (no arg) match.

## Performer-back-removed list (for user audit)

Inline state defs whose back was → CHOOSE_PERFORMER (rather than → CHOOSE_ACTION):
- 01011 (Servo Scarpa)
- 01015 (The Great Game)
- 01044 (Armed and Marshaled)
- 01147 (Let's Haggle)
- 01148 (Marooned)
- 01149 (Midnight Shipment)
- 01152a (Until Morale Improves)
- 01152b (Until Morale Improves)
- 01156 (Matchlock Musket)

User flagged these for audit because going back to CHOOSE_PERFORMER from the action's initial state would have skipped the CHOOSE_ACTION/CONFIRM steps. Now moot since the back is gone — but worth confirming the no-back direction is the intended UX.

## Files with no initial state (action goes straight to events)

These 12 actions have neither a Pattern A class nor inline def for their initial state — they fire `EventActionTriggered` and immediately go through the events/reactions cycle without an active-player intermediate:

- 01018, 01036, 01062, 01073, 01075, 01094, 01095a, 01095b, 01187, 01191, 01198, 01201, 01206, 02035

For these, only the action file itself was edited (3-call removal). No state edits needed.

## Anomalies / judgment calls

- `01180.php` had a `// This announcement is used in lieu of $this->announceAction()` comment AND a `// This announcement is used in lieu of` style comment about a custom notify call — I stripped the announceAction reference but kept the comment lead-in (it now reads as a normal `$game->notify->all` call without the lead-in note since the note no longer applies).
- `02036` state file is shared by both `02036a` and `02036b` actions (they're variants of "Rumors of the Crimson Roger" — `a` is the in-play action, `b` is a sub-effect). One state, edited once.
- `01095` (Patricia Moustakas multi-active state) is shared by `01095a` (in-play action) and `01095b` (challenge-issued sub-effect). State file is multipleactiveplayer with no back — left alone.
- `01154` and `02045` Pattern A files had no back transitions to begin with (they use sorcery transitions to sub-states). Skipped — nothing to remove.
- `02036a`'s `actFromActionPass` method also had setUsed/resetPass calls (in the "decline challenge" branch) — those were in scope and removed.
- `01044` had two call sites (one per branch of the "manipulate vs. send home" choice). Both removed.
- `01091` had two call sites (one for single-target heal, one for double-target heal-after-discard). Both removed.

## Skipped (verified out-of-scope)

None — all 70 action IDs in the user's list extend one of the four in-scope base classes. I verified each.

## Counts

- Action files modified: 70 (all in scope)
- Inline state defs with back removed: 16 (01011, 01012, 01015, 01017, 01019, 01020, 01044, 01046a, 01049, 01068, 01069, 01147, 01148, 01149, 01152a, 01152b, 01156, 01194, 01197, 01205 — actually 20)
- Inline state defs with NO back to remove (multipleactiveplayer): 5 (01008, 01035, 01038, 01180, 01192)
- Pattern A `State_*.php` files modified: 21 (01007, 01041, 01064, 01091, 01092, 01093, 01096, 01097, 01102, 01117, 01118, 01123, 01124, 01158 + 02001, 02002, 02013, 02023, 02025, 02033, 02034, 02036)
- Pattern A files left unchanged (no back to begin with): 4 (01095, 01154, 02045, 02047)
- ZombieTrait: 1 file (added confirm case, moved 14 state names from actBack → nextState group)
- Approximate lines removed: ~210 method calls + ~20 stale comments + ~14 actBack methods (4 lines each) ≈ 290 lines

## Things to double-check next session

- Smoke test a no-performer action like `01007` (Aldo Bussotti) — confirm flow still works through CONFIRM → DISPATCH → 01007 state → events.
- Smoke test a performer-required action like `01044` (Armed and Marshaled) — was → CHOOSE_PERFORMER, is now → EVENTS on zombie. The active-player flow is unchanged (player either picks attachment or zombie skips).
- Re-run pre-commit hook on a few action files. The hook checks for `setUsed()` and `announceAction()` requirements — those should now be relaxed (since centrally called). User said they're handling the hook update separately.
- The IN_PLAY_ACTION_CONFIRM zombie fall-through to `actBack()` is correct and safe (CONFIRM → CHOOSE_ACTION). Not affected by this refactor.
