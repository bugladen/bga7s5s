# Kaspar's Occupation (_02014) Audit

## What happened

Audited all classes related to card 02014 (Kaspar's Occupation). Found and fixed 4 bugs.

## Bugs & Reasoning

### Typo "Reknown" in notification text
Simple fix. The internal property name `$location->Reknown` is spelled that way throughout the codebase (legacy naming), but user-facing text should say "Renown". The notification had both spellings in the same string — clearly a typo on the second occurrence.

### City Forum selectable as move-FROM source
Same pattern as card 01150 which correctly excludes `forum-image`. The 02014 handler was missing this filter. Server-side validation in `actFromCardWithIds` catches it (`$locationName == Game::LOCATION_CITY_FORUM` throws), so it's a UX-only bug, but still wrong.

### Missing `addSortTagToCard.order` reset
This is the exact same bug that was found and fixed for 02005 during its audit. The `addSortTagToCard` function uses a static-like `order` property that increments with each click. Without resetting when leaving the ordering state, any future card-ordering mechanic in the same page session starts from a stale counter. The order values still sort correctly (descending), but the DOM attributes accumulate meaningless large numbers and the sort tags display wrong numbers to the player.

### Cannot discard zero cards
The card says "Discard any number" which in card-game parlance includes zero. The JS only enabled the confirm button when ≥1 card was selected. Added a "Keep All" button as a separate action. I verified the server handles an empty `$ids` array correctly — both foreach loops are skipped, all original cards remain, and it transitions to "cardsChosen" (the ordering state). Clean path.

## What I didn't change

The `$location->Reknown` property name is used everywhere as an internal identifier. I only fixed the user-facing notification string. Renaming the property would be a massive cross-cutting refactor for no functional benefit.

## Pattern observation

The `addSortTagToCard.order` reset is turning into a recurring bug across cards with ordering mechanics. Every card that uses `addSortTagToCard` in its event handler needs `delete this.addSortTagToCard.order` in its leaving state. Cards verified to have this: 02005_5 (fixed in prior audit), 02014_2 (fixed now). Other cards using this pattern (01134, 02002) should be checked if they come up for audit.
