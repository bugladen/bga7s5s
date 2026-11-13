# Raise the Stakes vs Gamble — player claim check

Player had `_02039` Raise the Stakes in hand, claimed Reaction not offered during a duel where the adversary (apparently) gambled.

## Card / code answer

**Text:** "Reaction: When the adversary announces their combat card • Add a threat to both participants."

**Trigger:** `Reaction_02039` listens only to `EventCombatCardAnnounced` (not `EventDuelPlayerGambled` / reveal). Gates: in duel, Risk in hand, announcer ≠ owner, not Used.

**Does gamble announce?** Yes for normal (and free) gamble choose: `actGambleCardChosen` queues `createCombatCardAnnouncedEvent` before `DuelPlayerGambled` and moves the card to the dueling line. Same event as hand play via `actDuelActionChooseCombatCard`.

**Exception:** Roll the Bones (`GAMBLE_TYPE_ROLL_THE_DICE`) deliberately skips CombatCardAnnounced — chosen card is not played as combat card.

## So

Raise the Stakes does **not** react to "gambles" as a verb/reveal. It reacts when the adversary **announces the chosen combat card**, and a completed normal gamble does that announce. If the claim is "no offer after they picked their gamble card," that would be a bug/missed offer — not by-design exclusion of gambles. If they expected an offer at reveal-before-choose, that is by design not the trigger.

## Follow-up: Subtle (_03012) from gamble, no prompt

Eddie: they never got the prompt; Subtle was the gambled combat card.

Subtle is a normal Risk (`_03012`) — Sorcerer Strega Reaction on intervene only. Nothing on CombatCardAnnounced / no eventCheck veto of announce.

Code path for normal gamble choose (`actGambleCardChosen`):
1. `createCombatCardAnnouncedEvent(actorControllerId, chosenId)` queued
2. `EventDuelPlayerGambled` queued
3. move to dueling line
4. `nextState("noManeuver")` → `DUEL_CHOOSE_GAMBLE_CARD_EVENTS` (has `reaction` / `pay` / `chooseNext`)
5. endOfEvents → apply combat card stats

Same announce event as hand play. Reaction_02039 gates unchanged: IN_DUEL, LOCATION_HAND, announcer ≠ owner ControllerId, !Used.

**Verdict:** should have been offered. Not excluded because it was a gamble or because the card was Subtle. If missing: check game log for `announces … as their Combat Card` (proves announce ran), whether Used already (once per day until dusk), and whether 02039 was still in hand at choose time. No code smoking gun found in this pass — worth a table-log dig if they have table id.

## Table log (Shadow77113 round 1)

Chronological (BGA newest-first flipped):

1. Round start (Shadow77113 defending)
2. Gamble reveal (blank inject names)
3. Sink 1 unchosen
4. **announces _ as Combat Card** ← CombatCardAnnounced fired
5. has gambled with _
6. Duel Update: played _ as Combat Card — Parry +3 Thrust +1 ← Subtle stats; went straight to apply stats
7. Ends round

No reaction UI / no Raise the Stakes play log. Card still in hand Day 1.

**Key code gate:** `Reaction_02039` returns early when `$event->playerId == $owner->ControllerId` — "adversary announces" only. If Raise the Stakes is in **Shadow77113's** hand, no offer is correct: they announced their own gambled Subtle. If it is in the **opponent's** hand, this log is a real miss (announce ran, reaction never interposed).

Blank inject codes across reveal/announce/gamble/update are a separate log-hydration problem (stats prove Subtle). Not the reaction gate.

## Clarifications (Eddie)

- Empty inject codes = copy/paste artifact, ignore.
- **Used / dusk is a red herring for this Risk.** Risk Reaction is played from hand and discarded on use. Still in hand on Day 1 ⇒ it was never resolved. `isAvailable()`/Used only matters if something stuck Used=true without discarding (pathological).
- **Player A** has Raise the Stakes. **Player B** (Shadow77113) is the round actor who gambled/announced. That is exactly the adversary-announce case → offer is required.

## Updated verdict

Real miss. Log: announce fired → gambled msg → apply-stats Duel Update with no reaction state in between. Code path should queue `reaction` from `DUEL_CHOOSE_GAMBLE_CARD_EVENTS` after `EventCombatCardAnnounced`.

Strongest remaining hypotheses (no smoking gun yet):
1. **Offer gate failed** on `Location == HAND` or `playerId == ControllerId` despite card sitting in the hand pile (known `card_location` vs serialized `Location` / stale ControllerId class — discard piles already get `repairDiscardPileLocations`, hands do not).
2. **Transition queued then fail-closed skipped** in `runEvents` (`!isAvailable()` / missing reaction id) — dequeued with `continue`, then `endOfEvents` → apply stats. Matches “no UI pause” log shape.

Next: table dump of Player A’s Raise the Stakes row (`card_location` vs serialized Location/ControllerId/Used), or add hand Location repair + a regression that runs announce through a real event drain.
