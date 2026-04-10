# The Song of Eisen (01045) Audit

## Card Text
**Resolve:** Add a Renown to [The Forums]. Put target Mercenary from the City Deck discard pile on top of the City Deck.
**Passive:** Your Leader gains +1[Influence] while parleying with a Mercenary.
**Reaction:** At the end of High Drama, if there are no available Mercenaries and attachments • Gain a Renown.

## Resolve — Add Renown to Forum: Correct
`handleEvent` on `EventResolveScheme` queues `createReknownAddedToLocationEvent` for `LOCATION_CITY_FORUM` with amount 1. Straightforward.

## Resolve — Mercenary from Discard to Deck: One Bug Fixed

### Bug: Missing server-side validation in actFromCardWithId
JS correctly filters `gamedatas.cityDiscard` to Mercenaries only, but the PHP `actFromCardWithId` accepted any card ID without verifying it's a Mercenary or in the discard pile. A crafted request could put any card on top of the city deck.

**Fix:** Added `hasTrait('Mercenary')` and `Location != LOCATION_CITY_DISCARD` checks before processing, consistent with how `actFromCardPass` already validates and how other cards (e.g., Action_01044) validate server-side.

### WHY only server-side validation added (not args-based)
The state uses `argsEmpty` and the JS builds the selection list from `gamedatas.cityDiscard`. This pattern works because the discard pile is public info already in gamedatas. Adding server args would be redundant — the validation just needs to exist server-side to prevent crafted requests.

## Passive — Leader +1 Influence: Correct
`getParleyDiscount` checks scheme at home + `$parleying` + performer is controlled by owner + performer is `Leader`. The `$parleying` flag is only true in the Mercenary parley flow (`actHighDramaRecruitActionParleyYes`), so it inherently scopes to "parleying with a Mercenary" without needing to check the target.

## Reaction — No Available Mercenaries/Attachments: Correct
`Reaction_01045` triggers on `EventHighDramaPhaseEnd`, scans all city locations for uncontrolled Mercenaries and available attachments. Only fires when both are absent. `performReaction` queues `PlayerGainsReknownEvent` for the "gainReknown" choice and marks used. Pass transitions out cleanly.

## Items verified
- Renown added to Forum on resolve ✓
- Mercenary selection from discard pile (JS + server validation added) ✓
- Pass blocked when Mercenaries exist in discard ✓
- Leader +1 Influence during Mercenary parley ✓
- Reaction condition (no uncontrolled Mercenaries, no available attachments) ✓
- Reaction gain Renown + mark used ✓
- No linter errors ✓
