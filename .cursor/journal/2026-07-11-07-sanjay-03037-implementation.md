# Sanjay (_03037) — Daring Tomcat Leader

## Orientation

Eddie asked to finish `_03037` via create-character. Stub had wrong Name (La Voix copy-paste); Eddie fixed Name/Title. Text: (1) gambled combat cards +1 Riposte, (2) Reaction on challenge refused → Collect Renown from location, (3) City Action → Influence challenge if opponent has fewer hand cards.

## Plan / WHY

1. **Passive** — `EventDuelCalculateCombatCardStats` with `$event->gambled` (set in `stResolveCombatCard` from duel_round.gambled). Gate `actorId == $this->Id`. Mirror Yevgeni but only when gambled. WHY not DUEL_GAMBLED global alone: the event already carries the authoritative round gambled flag (works for Roll-the-Bones path too via `$event->gambled`).

2. **Reaction** — Pattern D button reaction on `EventChallengeRejected`, `challengerId == owner.Id`. Collect = `createRenownRemovedFromLocationEvent` + `createPlayerGainsReknownEvent`. Valid-target gate: location Renown > 0. WHY Reaction not Joern-style passive handleEvent: printed keyword is **Reaction:** (optional Collect/Pass), same shape as Yevgeni 01116a.

3. **City Action** — Pattern F Influence challenge. Not a basic challenge and no Engage cost → `SANJAY_CHALLENGE_TYPE` kept OUT of auto-engage list and **no manual engage either**. Eddie correction: Sanjay is not engaged by this action. Hand-size filter on opposing targets so the action is only offered when it can succeed.

4. Remove `initializeFaction` per Leader checklist (framework sets faction at setup). Keep Aragosta trait as printed.

## Done

Implemented all three abilities + wiring. `SANJAY_CHALLENGE_TYPE = 22` (after SWORN_SWORDS 21). No intervention/refuse restrictions — only Game.php + JS constant needed beyond Pattern F picker; deliberately NOT added to auto-engage list.

Hand-size check uses `getGameDeckObject()->getPlayerHand`. Filtered at availability + target list so the action never offers a dead pick.

Removed `initializeFaction("Castille")` from the stub — skill Leader checklist. Eddie had already fixed Name/Title.

**Correction (Eddie):** Initially copied Don Constanzo's conditional-engage. Wrong — this action never engages Sanjay. Type stays out of auto-engage AND no `createCardEngagedEvent`. WHY the dedicated type still exists: NORMAL would auto-engage via `stIssueChallenge`.
