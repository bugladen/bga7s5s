# Penya (04cd01b) + Move Along (01056) — defender 01056_2 loop

## Report
Player A Penya Action_04cd01b pulled Move Along from B discard. A used stolen Action_01056:
02003 challenged 01037. A fired Stiletto Reaction_01022. B landed on
`highDramaPhase01056_2`; Move Home / Continue both looped back to the same state.

## WHY
Same hole as Improvising + Press the Advantage (`2026-11-13-07-improvising-01058-move.md`),
called out there as unfinished for `_04cd01_RiskClone`.

Pay discards the `_04cd01_RiskClone` shell. Old handler immediately queued permanentlyHide
+ sink original + ActionResolved. `buildCity` omits Permanently Hidden.

Challenge still progressed because:
- Target pick / early acts use `getCardById` DB fallback → Action_01056 still ran
- Technique → ChallengeIssued → Stiletto reaction window worked

Then `stChallengeActionCheckCancelled` MOVE_ALONG branch restores TRANSITION_SOURCE_ID /
TRANSITION_INTERNAL_ID from `CHOSEN_ACTION` via `getInPlayActionById` (no DB fallback).
Shell gone → restore skipped → INTERNAL stayed Stiletto/technique. Defender click:
`actFromCardWithId` hit Reaction_01022's empty `actFromReactionWithId` stub → no
`nextState` → same state again ("loop"). Stiletto made the clobber obvious; technique
alone would also break Penya+Move Along.

Card text: "After it resolves, sink it." Discard-from-hand is pay-time, not resolve-time.

## Fix
Port `_01106_RiskClone` PendingCleanup to `_04cd01_RiskClone`:
- Discard → PendingCleanup only (shell stays in Owner discard → buildCity)
- EventActionResolved for ControllerId when CHOSEN_ACTION starts with `{shellId}_` →
  hide shell, sink original by OwnerId
- No second ActionResolved (stolen action / challenge cancel already emits one)

Action_04cd01b comment updated to match.

## Not doing here
`_01124_RiskClone` / `_01154_RiskClone` still hide on discard — same multi-step hole if
someone steals a multi-step Risk through those paths.

## Tests
- Card_04cd01_RiskClone_Test rewritten for PendingCleanup / resolve / wrong CHOSEN_ACTION / hub
- Action_04cd01b: Penya Move Along stays findable after discard; continue → 01056_5
