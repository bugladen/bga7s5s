# Bastien Girard (01063) Audit

## Card Text
> "Your characters at Bastien's location gain 'Technique: Swap this character with a Musketeer at this location.'"
> "Technique: When your round ends, if Bastien was not wounded during it, wound the adversary. (Issuing a challenge is not a round of a duel.)"

## Files Audited
- `modules/php/cards/_7s5s/_01063.php` — main card class
- `modules/php/cards/_7s5s/techniques/Technique_01063.php` — wound adversary technique
- `modules/php/cards/_7s5s/techniques/Technique_01063Swap.php` — swap technique (granted to allies)
- `modules/php/cards/_7s5s/_01067.php` — Jean Urbain (reference aura pattern)
- `modules/php/cards/TechniqueTrait.php` — addTechnique/removeTechnique
- `modules/php/StatesTrait.php` — duel state flow (stDuelStarted, stDuelEndOfRound)

## Bug Found & Fixed

**EventCharacterRecruited handler was missing two guard checks** compared to the established pattern in `_01067` (Jean Urbain) and the card's own `EventCardMoved` handlers:

1. `$character->Id != $this->Id` — Bastien was granting himself the swap technique on recruitment. The EventCardMoved handlers already exclude Bastien from self-granting. The aura is meant for other characters at the location, not the aura source.

2. `$character->Location != Game::LOCATION_PLAYER_HOME` — The swap technique could be granted while at the player home. EventCardMoved handlers all guard against home locations.

WHY this matters: Without the self-exclusion check, Bastien would accumulate a Technique_01063Swap on himself when recruited. The `addTechnique` duplicate check (`in_array` with loose equality) would catch repeated adds, but the first add on recruitment would stick. Bastien would then have both his native wound-adversary technique AND the swap technique, which doesn't match the card design — his abilities are granting aura to others + his own wound technique.

## Verified Correct

**Technique_01063 (wound adversary):** `IN_DUEL` is set in `stDuelStarted`, which runs AFTER challenge resolution. So `isAvailableToPlayer` naturally blocks activation during challenges. The parenthetical "(Issuing a challenge is not a round of a duel.)" is just a rules clarification — the code already handles it correctly through state flow. The IsActive/BastienWoundedThisRound tracking is sound.

**Technique_01063Swap (swap with musketeer):** Handles both challenge (EventGenerateChallengeThreat) and duel (EventDuelCalculateTechniqueValues) swap scenarios. The actFromTechniqueWithId validation is thorough. isAvailableToPlayer correctly requires another friendly Musketeer at the same location.

**EventCardMoved aura management:** All three branches (Bastien moves, character arrives at Bastien's location, character leaves Bastien's location) are correctly implemented with proper home-location guards and self-exclusion.
