# Polished Flintlock — Katain window lost when Vissenta ignores wound

## Report
Katain used Polished Flintlock City Action on Don Vissenta (CAD Leader). Vissenta ignored the wound with Reaction_05Cooper. Katain never got Reaction_02011 to copy the ranged ability. Looked like ignore canceled his window.

## Root cause
Not Vissenta's cancel. `Action_01049` already-engaged path queued only wound + engage flintlock — **no** `EventRangedAbilityPlayed`, **no** `EventActionResolved`.

Katain listens on `EventRangedAbilityPlayed`. Vissenta listens on `EventCharacterBeingWounded` (cancel-first). So only her UI opened; after ignore, events drained with nothing left for Katain.

Engage-or-wound path (`01049_2`) already fires ranged after either choice — fine. Gap was only the auto-wound when target was already Engaged.

Sibling Matchlock `Action_01156` already-engaged path correctly queues ranged + ActionResolved. Flintlock was missing the same finalize.

## WHY not a Cooper bug
Cancel-first ignore only sets `$event->canceled` on BeingWounded. Does not `deleteEventBatch` or strip later MEDIUM events. If RangedAbilityPlayed is queued, it still runs (priority 3 before reaction UI at 6), and Katain's transition queues alongside hers.

## Fix
Already-engaged branch: queue `createRangedAbilityPlayedEvent` + `createActionResolvedEvent` after wound/engage — mirror Matchlock and `01049_2`.

Tests: engaged path asserts ranged + resolved; refuse path also asserts both (were missing).

## Do not regress
- Keep ranged after engage OR wound on `01049_2` (journal 2026-09-23-05 / Vantage Point lesson).
- Do not put RangedAbilityPlayed in the wound/engage batchId — cancel-batch would wrongly kill Katain's signal.
