# Cats in Every Corner (_04037)

## Classification
Printed: **En Garde Academic Action:** Discard an available City Card at this location • Look at the top five cards of the City Deck. You may add one to this location, then sink the rest. Then, your performer may perform another action. *(It must be performed and they must be the performer)*

- Heading is **Action** not City Action → `RiskAction`, start from `parent::getPerformersForAction` (home eligible in principle).
- En Garde = `!$Engaged` precondition, not Engage cost.
- Academic = `hasTrait` gate, not Sorcerer.
- Discard before the bullet = **cost** (`asEffect = false`). Chooser is available City Card at performer location (`ICityDeckCard` + `!isControlled()` + `canBeDiscardedFromCity()`). Mirror `_01112b` / `Action_04015` filter. No printed "Target" → no Cesca.
- Look at top 5 / may add one / sink rest = chooseList (02014/03052 peek stash in `CHOSEN_CARD`). Pass = add none, still sink. Add via `createCityCardAddedToLocationEvent`. Sink via `createCardAddedToCityDeckEvent(..., false)`.
- Extra action italic → Pattern A.2 both globals. Pass allowed.

## WHY peek before discard event flushes
`getCardsOnTopOfCityDeck` reshuffles City Discard when the deck is short. If we peeked after the discard landed, the just-discarded card could shuffle back into the look. Peek in `actFromActionWithId` (deck unchanged by a still-queued discard), then queue the discard.

## WHY empty deck still resolves
Look is the effect, not a gate. Complete as much as possible: discard → skip `_2` → still grant locked extra action.

## WHY no Cesca
Chooser is a City Card, not a character, and text never says Target.

## Implemented
- `_04037` + `Action_04037`
- `State_highDramaPhase04037` (in-play discard chooser) + `_2` (chooseList may-add)
- Wired `states.inc.php` / `States.php` / bas JS trio
- Skill B.10 in SKILL.md / pattern-b / references / checklist

## Unfinished
Playtest: Academic en garde at a location with an available City Card; confirm peek does not include the discarded card when the City Deck is short; Pass on `_2` still sinks and grants the locked extra action.
