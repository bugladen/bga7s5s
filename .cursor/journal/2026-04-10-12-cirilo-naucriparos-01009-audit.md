# Cirilo Naucriparos (01009) Audit

## Card Text
> Your Mercenaries gain Brute. (Brutes do not count against your Crew Cap, go to the discard pile when destroyed, and are discarded from play at the end of the day.)
> **City Action:** Engage Cirilo • Recruit target available Mercenary at this location. They lose Negotiable and have 1 cost instead of their printed value.

## Verdict: No Bugs Found

### Passive — "Your Mercenaries gain Brute"
Three event handlers cover all entry paths:
1. `EventApproachCharacterPlayed | EventCharacterMustered` with `characterId == $this->Id` — when Cirilo enters play, scans all controlled Mercenaries and adds Brute ✓
2. `EventCharacterRecruited` with `playerId == $this->ControllerId` — when any Mercenary is recruited after Cirilo is in play, adds Brute ✓
3. `EventCharacterDestroyed` with `characterId == $this->Id` — when Cirilo is destroyed, removes Brute from all controlled Mercenaries and queues `EventCharacterLostBrute` for crew cap re-check ✓

No gap for approach/muster: every Mercenary in the game is a CityCharacter (recruited, not played from faction deck). So `EventCharacterRecruited` covers all possible Mercenary entry paths after Cirilo.

### City Action — Engage, Recruit, Lose Negotiable, Cost=1

**Availability** (`Action_01009::isAvailableToPlayer`): Parent checks (owner controlled, not used), Cirilo NOT engaged, Cirilo in city, uncontrolled Mercenaries exist at Cirilo's location. All correct ✓

**"Engage Cirilo"**: `createCardEngagedEvent` queued in `handleEvent` on action trigger ✓

**"Recruit target available Mercenary at this location"**: Transitions to `HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_MERCENARY` via "01009" transition. `argsHighDramaRecruitActionChooseMercenary` correctly filters for uncontrolled Mercenaries at the performer's (Cirilo's) location ✓

**"They lose Negotiable"**: The transition skips `HIGH_DRAMA_RECRUIT_ACTION_PARLEYABLE` and `PARLEY` states entirely, going straight to `CHOOSE_MERCENARY`. Player never gets the parley option. Correct ✓

**"have 1 cost instead of their printed value"**: Three places all correctly override to 1:
- `ArgumentsTrait::argsHighDramaRecruitActionPayForMercenary` — PHP args ✓
- `FrameworkActionsTrait::actRecruitMercenary` — PHP validation ✓
- `OnEnteringState.js` — JS display in both choose and pay states ✓

### Brute Reminder Mechanics
These are framework behaviors, not _01009-specific, but verified they apply correctly to Cirilo's Mercenaries:
- **Crew Cap exemption**: `getCharacterCountByPlayerId` checks `hasTrait("Brute")` and excludes by default ✓
- **Discard on destroy**: EventHub uses `instanceof Brute` (class check, not trait check). Mercenaries with gained Brute trait go to locker — per Agent006 ruling this is intentional ✓
- **End-of-day discard**: StatesTrait checks `hasTrait("Brute")` — correctly discards gained-Brute Mercenaries. CityCharacters go to city discard, faction characters to player discard ✓

### Back Button Behavior
No back button for either CHOOSE_MERCENARY or PAY_FOR_MERCENARY in Cirilo's flow. Intentional — Cirilo is already engaged and the action is marked used, so there's nothing to undo.

### Minor Observation (Not a Bug)
JS `crewCapCheck()` in Utilities.js doesn't exclude Brute characters from the count. This could cause a false "you'll exceed crew cap" warning dialog when recruiting via the normal Recruit button. This is:
1. Not triggered by Cirilo's action (goes through in-play action flow, not normal recruit button)
2. Only cosmetic (server-side validation is correct)
3. Pre-existing, not _01009-specific

### Previous Fix Reference
A cost display bug was fixed in a prior session (see `2026-04-02-02-cirilo-01009-recruit-cost-display.md`). Both ArgumentsTrait and OnEnteringState.js now correctly show cost=1.
