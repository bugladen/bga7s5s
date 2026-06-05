# Night of Drinking (01109) — react on announce instead of EventRiskPlayed

## Change

Reaction_01109's Risk-cancel handler moved from `EventRiskPlayed` to
`EventActionActivated`. Detect "this is a Risk being played" via
`$event->theah->getCardById($event->sourceId) instanceof Risk`.

Other branches (EventManeuverActivated for cancelling Risk maneuvers,
EventDuelCalculateCombatCardStats for Not Today) are unchanged.

## Why

User-driven: keep cancel-on-announce semantics consistent with other "react to
the announce" cards. EventActionActivated is queued by `CardAction::announceAction()`
which all RiskAction plays funnel through, so detection is sound. EventRiskPlayed
import is no longer needed.

## Timing concern + fix

`FrameworkActionsTrait::actPayForInHandAction` queues, in order:
1. `EventActionActivated` (via `announceAction()`, line 971)
2. `EventRiskPlayed` (line 1000)
3. `EventActionTriggered` (line 1003)

So when 01109 now fires on EventActionActivated and the player cancels, the
queued EventRiskPlayed for this risk is still sitting in the queue. Other
reactions watching for "a Risk was played" (Cat's Embargo, anything else)
would otherwise see the cancelled Risk and trigger.

Fix: added `Theah::deleteRiskPlayedEvents(int $riskId)` /
`DB::deleteRiskPlayedEvents(int $riskId)` and call it alongside the existing
`deleteActionTriggeredEvents` / `deleteRiskReactionTriggeredEvents` in the
cancel handler. SQL pattern matches `EventRiskPlayed` AND
`riskId";i:{$riskId};` — the trailing `;` prevents `riskId 1` matching
`riskId 12`, etc.

Guarded with `if ($riskId <= 0) return;` to avoid the empty-string wildcard
disaster that hit deleteManeuverEvents (see 2026-04-17-01). RiskId is `int`
so it can't be `''`, but `0` is the default and would match `i:0` in many
unrelated events.

## What did NOT change

- `Reaction_01140::revertCancellation` path (Stubborn restore) — still fires
  in the cancel branch, just triggered from a different upstream event.
- Decline path — unchanged.
- The `_01169` (Not Today) edge case — still routed via
  EventDuelCalculateCombatCardStats, unrelated to this swap.
- Tooth and Claw `Reaction_02030a` and similar announce-listeners — they
  already listen on EventActionActivated, so adding 01109 to the same trigger
  doesn't introduce a new ordering surface.

## Risks I'm watching

- Anything else that depends on EventRiskPlayed firing for a Risk that 01109
  cancels will no longer see it. Audited the codebase via grep: only 01109
  itself (now removed), FrameworkActionsTrait (the producer), and the journal
  notes reference EventRiskPlayed. Safe today.
- If a future card listens for EventRiskPlayed and wants to *see* cancelled
  plays for some reason, this delete will hide them. That seems fine —
  cancelled plays shouldn't fire "after a Risk is played" hooks.
- EventActionActivated also fires for non-Risk actions. The
  `$risk instanceof Risk` guard rejects those. Verified the only other
  source of EventActionActivated is `LocationAction` which has sourceId=0.

## Files

- `modules/php/theah/DB.php` — added `deleteRiskPlayedEvents`
- `modules/php/theah/Theah.php` — added passthrough
- `modules/php/cards/_7s5s/reactions/Reaction_01109.php` — swap handler,
  drop EventRiskPlayed import, add EventActionActivated import, call
  `deleteRiskPlayedEvents` in cancel handler
