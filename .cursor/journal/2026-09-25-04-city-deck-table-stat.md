# City Deck table statistic

## What
Added table stat `city_deck` (id 22) so end-of-game / post-game BGA stats show which City Deck table option was used.

## Labels (must match gameoptions.json option 100 values)
0 Core Set, 1 City Deck Update 2024, 2 City Deck Update 2026

## WHY id 22
Next free id after player `crew_cap_overage_locker` (21). value_labels are shared table+player per id — cannot reuse 10–21.

## WHY set in `DeckTrait::buildDecks`
That is where `OPTIONS_CITY_DECK` is already read and the deck is built. Same pattern as leader stat (set when decks materialize). Init in `setupNewGame` to `CITY_DECK_CORE` (0) so the stat is never undefined if something looked before buildDecks.

## Do not regress
- New city deck builds → append to gameoptions.json AND value_labels id 22 AND Game::CITY_DECK_* (never reorder once live)
- Do not change id 22 while public games run
