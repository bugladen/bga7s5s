# Torvo × Vittoria — targeting before accept/decline

## Bug
Torvo (02034) City Action targeted Vittoria → she had to accept/decline the challenge offer BEFORE her Reaction could put a Thug in play. Printed order is Target → (reactions) → They may issue…. Also: Thug with Combat < 2 should make the whole ability fail (no offer, no draw).

## WHY it was wrong
Action_02034 queued `02034_2` (accept/decline) directly on pick — never fired `EventCharacterTargeted`. Vittoria only saw Torvo later via `EventChallengeIssued` (after accept + technique). Same class of bug Sanjay 03037 / Dabney / framework challenge picker already fixed.

## Fix plan
Mirror Action_03037:
1. act pick → CharacterTargeted only (batchId)
2. handleEvent on surviving CharacterTargeted → sync CHOSEN_CARD to (possibly redirected) target → queue `02034_2` for that controller, stamped with batchId so hold's deleteEventBatch strips a premature offer if Torvo processes before Vittoria
3. Reaction_01014 cancelEvents: ActionResolve for Action_02034 when CharacterTargeted abort outside 4511 (UL already special-cases Action_01078 the same way — hub-exit never ActionResolves on its own)

## Not doing
Not changing ChallengeIssued path for Vittoria — still needed for other challenge shapes. Not generalizing ActionResolved to all CharacterTargeted cancels (Giacinto already queues unbatched ActionResolved before cancel in some orderings → double resolve).

## Done
- `Action_02034`: act pick → CharacterTargeted only; surviving handler syncs `CHOSEN_CARD`, queues `02034_2` with batchId + deleteQueuedTransitionsNamed
- `Reaction_01014::cancelEvents`: ActionResolve for `Action_02034` when CharacterTargeted abort outside 4511 (Combat < 2 Thug → ability fails, no draw, no softlock)
- Tests: Action_02034 CharacterTargeted/survive/cancel/redirect; Reaction_01014 Torvo offer + invalid Combat cancel + valid re-release

## Feel
Clean Sanjay clone. The Action_02034 special-case in Vittoria is a little ugly (same as UL→01078) but generalizing would double-resolve Giacinto — leave it. Sanjay has the same softlock shape for failed redirects; his hand-count gate usually still passes on same-controller Thug so less likely to bite.

## Unrelated suite failures caught on full run
- `Card_05Dandy` stats lagged card: Resolve 8 (377cce19), CrewCap 6 (e074aca4) — test updated.
- `Action_01154` RiskClone discard still expected immediate Permanently Hidden after 787bf092 queued permanentlyHide — align with `_01106_RiskClone` test (hand until hub, assert `permanentlyHide` on remove event).
