# Rattle the Rigging (_04009) — create-risk

## Classification

Printed Text:
> **En Garde Action:** Target opponent chooses one of their characters opposing your performer. The chosen character issues a [Combat] challenge to your performer. If your performer is a **Duelist**, their first combat card gains +1[Riposte].

- Pattern B `RiskAction` (not City Action)
- **En Garde** label = performer precondition `!$Engaged` (create-city-character Pattern C note; not an Engage cost)
- "Target opponent" = player chooser, NOT character Target → **no** `IRiskThatTargetsCharacters` / Cesca interfaces
- Forced enemy challenges performer → closest mirror `_01078` Defending Honor (inverted: they pick challenger, defender is fixed)
- Duelist first-combat-card +1 Riposte → sticky Action field + `EventDuelCalculateCombatCardStats` (Maneuver_01084 / Sanjay shape)

## Design decisions (WHY)

1. **Custom `RATTLE_THE_RIGGING_CHALLENGE_TYPE` off auto-engage list** — mirror Defending Honor / Sanjay. Forced "enemy issues challenge" does not free-engage the opponent for you. Contrast Arrogant NORMAL which auto-engages *your* performer.

2. **Two GameStates:** `04009` opponent buttons → `04009_2` opponent picks challenger character. Then `"04009_3"` → shared `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` (target fixed = our performer; swap CHOSEN_PERFORMER to enemy challenger, CHOSEN_TARGET = defender).

3. **Single valid opponent:** auto-set CHOSEN_OPPONENT and skip button state — still "targeted" mechanically.

4. **Riposte sticky on Action** (`FirstCombatCardRiposteCharacterId`) — Risk lands in discard; discard is in `buildCity` so Action still receives duel calc events. Clear on apply / `EventDuelEnd` / `EventActionResolved` when `!IN_DUEL` (cancel path).

5. **No Maneuver** — Text has none; do not invent from adjacent stubs.

## Implemented

All of the above. php -l clean on touched PHP.

## Feel / open questions

Closest mirror was Defending Honor but inverted (you pick enemy challenger there; here opponent picks which of theirs challenges you). The auto-engage skip feels right for forced-enemy challenges but Eddie may correct if rules say issuing always engages. Didn't add skill pattern doc — this shape is rare enough that Defending Honor + this journal should be enough unless a third card shows up.
