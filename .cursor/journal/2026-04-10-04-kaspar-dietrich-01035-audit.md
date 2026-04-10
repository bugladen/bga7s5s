# Kaspar Dietrich (01035) Audit

## Card Text
- **Passive:** When Kaspar parleys, he gains +2 [Influence].
- **City Action:** Engage Kaspar • Reveal cards from the City Deck until you find a Mercenary. Kaspar may recruit it and can parley even while engaged. Sink the rest.

## Passive — +2 Influence when parleying
`_01035::getParleyDiscount()` adds 2 to the discount when `$performer->Id == $this->Id && $parleying`. This stacks with `Character::getParleyDiscount()` which adds `$this->ModifiedInfluence` (Kaspar's base Influence of 2). Net result: effective Influence for parley = base 2 + bonus 2 = 4. Correct.

## City Action — Engage, Reveal, Recruit, Parley, Sink

### Availability
`isAvailableToPlayer` correctly gates on: parent checks (owner controlled, not used), Kaspar NOT engaged, Kaspar in city. Must be unengaged before the action since engagement is part of the action effect.

### Engagement
`handleEvent` fires `createCardEngagedEvent` for Kaspar. Correct.

### Reveal from City Deck
Uses `revealFirstCardTypeFromCityDeck($playerId, "Mercenary", $kaspar->Id)`. This function reveals cards one at a time until a Mercenary trait is found, handles shuffle-discard-into-deck retry if none found on first pass. Correct.

### "May recruit it"
State 01035_3 presents Recruit/Pass choice. Pass sinks the mercenary via `createCardAddedToCityDeckEvent(false)` (bottom of deck = sink). Correct.

### "Can parley even while engaged"
This is the interesting one. In the normal recruit flow, `stHighDramaRecruitActionParleyable` checks `$performer->Engaged` and routes to "notParleyable" if true. Since Kaspar is engaged by his own action, the normal flow would block parley. Kaspar's custom state flow (01035_4) bypasses `stHighDramaRecruitActionParleyable` entirely — the parley choice is always available in 01035_4 regardless of engagement. Correct by design.

### "Sink the rest"
`revealFirstCardTypeFromCityDeck` with `$discardInsteadOfSink=false` (default) shuffles non-found revealed cards and puts them at the bottom of the city deck via `insertCardOnExtremePosition($cardId, LOCATION_CITY_DECK, false)`. This is the standard "sink" pattern in this codebase. Correct.

### PERFORMER_PARLEYED not set — intentional
Kaspar's flow doesn't set `Game::PERFORMER_PARLEYED`. In `actHighDramaRecruitActionPayForMercenary`, this defaults to `false`, which prevents: (a) a redundant parley notification (Kaspar's action already sends its own), and (b) a second engagement event (Kaspar is already engaged). This is correct behavior — not a bug.

### State flow
01035 (multipleactiveplayer: acknowledge revealed cards) → 01035_2 (game: check if mercenary found) → found: 01035_3 (activeplayer: recruit or pass) → 01035_4 (activeplayer: parley or not) → PAY_FOR_MERCENARY. Back button from PAY goes to 01035_4 via "backKaspar" transition. Clean flow.

## Items verified as correct
- +2 Influence discount stacks with base Influence ✓
- Engagement event fires before reveal ✓
- City Deck reveal with Mercenary trait match ✓
- Optional recruit via state 01035_3 ✓
- Parley bypass via custom state flow ✓
- Sink = bottom of city deck (shuffled) ✓
- CHOSEN_PERFORMER set to Kaspar for payment flow ✓
- RECRUIT_TYPE set to KASPAR_RECRUIT_TYPE for UI back-button behavior ✓
- JS UI: all three custom states have entering/leaving/button handlers ✓

## No bugs found
Implementation fully matches card text.
