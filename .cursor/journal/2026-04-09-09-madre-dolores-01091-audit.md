# "Madre" Dolores (01091) Audit

## Card Text
> Traits: Academic, Castille
> **City Action:** Target a character at this location, or two characters instead by discarding a card • Heal a wound from each targeted character.

## What checks out

### City Action gate
- `isAvailableToPlayer` checks `cardInCity($owner)` — correctly prevents activation when not in the city. ✓

### Target selection (State 1 — `highDramaPhase01091`)
- Provides list of characters at Madre Dolores's location with `Wounds > 0`. ✓
- Front-end sets `numberOfCardsSelectable = 2` — player can pick 1 or 2 characters. ✓
- Front-end shows confirmation dialog when player selects fewer than max (i.e. picks 1 when 2 is possible). ✓
- `isValidTargetForAbility` enforces same location + wounded. ✓
- No controller restriction — any character can be targeted (card doesn't say "friendly" or "enemy"). ✓
- Madre Dolores can target herself if wounded (card doesn't say "another character"). ✓

### One-character path
- Heals 1 wound via `createCharacterBeingHealedEvent`. ✓
- No discard required. ✓
- Sets used, announces, resets pass count, transitions to events. ✓

### Two-character path
- Stores chosen IDs in `CHOSEN_TARGET` global, transitions to State 2. ✓
- State 2 (`highDramaPhase01091_2`) requires player to discard a card from hand. ✓
- Front-end highlights the two chosen characters (display only via `highlightCardsAsChosen`), enables hand selection. ✓
- Validates selected card is in player's hand. ✓
- Discards the card, then heals 1 wound from each of the two stored characters. ✓
- Sets used, announces, resets pass count, transitions to events. ✓

### Back / undo flow
- State 1 back → returns to action chooser. ✓
- State 2 back → returns to State 1 (character re-selection). ✓
- Neither path calls `setUsed` until the action fully resolves. ✓

## No functional bugs found

All card text functionality is correctly captured.
