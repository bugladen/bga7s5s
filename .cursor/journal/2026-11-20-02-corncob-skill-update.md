# Create Character skill — Corncob lessons

## Why
User asked to fold `_05Corncob` implementation lessons into create-character so the next agent does not re-learn: (1) "each deck" includes City Deck, (2) merged discard Weapon lists still remove from the correct pile, (3) Covert Technique ≠ En Garde Technique.

## What landed
- **SKILL.md** shape rows: Forced end-of-Planning each-deck mill; Covert Action Weapon from any discard; Covert Technique +1 Parry; `EventPhasePlanningEnd` in phase list.
- **pattern-a**: subsection "Forced: discard top N of each deck" + lifecycle row for `EventPhasePlanningEnd`.
- **pattern-c**: subsection "Covert Action: Weapon from any discard → equip paying costs" + Action examples table row.
- **pattern-e**: subsection "Covert Technique +N Parry".
- **references**: `_05Corncob` / Action / Technique entries.
- **checklist**: 83a Covert Technique; 109a Covert Weapon Action; 109b Forced mill; 103 CAD id list + Corncob state digits; 106 Insimore.

## Load-bearing WHYs to keep
- City Deck is part of "each deck" — earlier faction-only read was wrong; user corrected.
- Per-deck announce before card-level discard notifs — silent mills hide empty vs milled decks.
- FakeGame still-in-deck Location gate — shared `topFactionCards` otherwise double-mills into player 2.
- Merged chooseList is fine because remove event keys off current Location, not list provenance.
- HD Action may use Teens `PAY_STATE_EQUIP_ATTACHMENT`; Reactions (Vulture/Tomas) must not.
- Covert Technique gates uncontrolled, not `!$Engaged`; PlusOneParry stays duel-only.
- CardNumber 13 vs Snow White: scheme has no HD states → `405013` free.

## Unfinished
None — skill update complete.
