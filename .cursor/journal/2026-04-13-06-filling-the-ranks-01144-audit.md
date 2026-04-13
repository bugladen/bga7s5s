# Filling The Ranks (01144) Audit

## Card Text

> Add a Renown to any location. Then, if you have the fewest Renown, add a Renown to a different location. (Fewest cannot tie.)
>
> **Leader Reaction:** At the beginning of High Drama, if you have the fewest characters • Recruit an available Mercenary to your Home. They lose Negotiable and their cost is reduced by your performer's [Combat], [Finesse], or [Influence].

## Bug Fixed: Missing Mercenary Trait Filter in Leader Reaction

The Leader Reaction's recruitment flow allowed selecting ANY uncontrolled character in the city, not just Mercenaries as the card text requires. This was present in three places:

1. **handleEvent availability check** (Reaction_01144.php:54) — `array_filter` checked `instanceof Character && !isControlled() && cardInCity()` but not `hasTrait("Mercenary")`. This meant the reaction would trigger even if only non-Mercenary characters were available.

2. **actFromReactionWithId server validation** (Reaction_01144.php:128-147) — validated character exists, is uncontrolled, and is in city, but never validated it's a Mercenary. A crafted request could recruit a non-Mercenary.

3. **Frontend OnEnteringState** (OnEnteringState.7s5s.js:430) — made all uncontrolled city Characters selectable instead of filtering for `card.traits.includes('Mercenary')`.

Added `hasTrait("Mercenary")` / `card.traits.includes('Mercenary')` to all three locations. Same pattern of missing trait validation seen in 01143 audit (Mercenary check on EventCharacterRecruited).

## Everything Else Checks Out

- **Scheme Resolution**: First state adds renown to chosen location, then checks if active player has fewest renown (no tie allowed via `$lowestScoreCount == 1`). If yes, transitions to second state for a different location. The second state excludes the first chosen location via `argsFromCard` passing the chosen location and the JS filtering it out. Correct.

- **Fewest Renown tie handling**: Counts players at lowest score, only proceeds if exactly 1. Matches "(Fewest cannot tie.)" text. Correct.

- **Fewest characters tie handling**: `getPlayerControllingFewestCharacters()` returns null playerId on tie, so the reaction won't trigger on ties. Correct — the card text says "if you have the fewest characters" implying sole possession.

- **"Lose Negotiable"**: Handled implicitly by the custom state flow — player goes directly from mercenary selection to payment without passing through parley states. Same pattern as Cirilo (01009). Correct.

- **Cost reduction**: Uses `max(ModifiedCombat, ModifiedFinesse, ModifiedInfluence)` of the leader. The card says "reduced by your performer's [Combat], [Finesse], or [Influence]" — using the max is the optimal player choice, which is standard for "X or Y or Z" card text. Correct.

- **Move to Home**: After recruitment, `createCardMovingEvent` moves mercenary to `LOCATION_PLAYER_HOME` with `unstoppable = true`. Correct per card text "to your Home".

- **`isAvailable()` check**: Called in handleEvent. `setUsed()` called after fillRanks. Correct per pre-commit hook requirements for CardReaction.

- **Scheme location**: Checked that scheme must be in `LOCATION_PLAYER_HOME`. Standard for schemes. Correct.
