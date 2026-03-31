# Guillén de Murrieta (_01064) Audit

## Card Text
> While an opponent has more Renown than you, Guillén gains +1 [Com].
> City Action: Discard a card • Move an adjacent Renown to this location.

## City Action — Correct
The two-step flow (choose card → choose adjacent location) is solid. `isAvailableToPlayer` properly gates on: Guillén being in the city, player having hand cards, and at least one adjacent location having renown. The resolution discards the card, removes 1 renown from the chosen location, and adds 1 to Guillén's location. No issues.

## Passive Ability — Bug Fixed

### The Bug
`checkRenown()` used `$renown >= $playerRenown` with an early `break`, then filtered ties in the "add bonus" branch with `$highestRenown != $playerRenown`. This fails in multi-opponent scenarios:

- Player has 5 renown, Opponent A has 5 (tie), Opponent B has 7 (strictly more)
- Loop hits Opponent A first → `5 >= 5` is true → breaks immediately
- `highestRenown` is 5, which equals `playerRenown` → tie check blocks the bonus
- Opponent B's 7 is never seen → bonus incorrectly not applied

### The Fix
Changed the comparison to `$renown > $playerRenown` (strictly more, matching card text exactly). Now the loop only matches opponents with genuinely more renown. The `break` is safe because we only need to find ONE qualifying opponent. Removed the `$highestRenown` tracking and tie-check variables — no longer needed.

Simplified variable naming from the confusing double-negative `$playerHasHigherRenown` to the direct `$opponentHasMore`.

### Persistence Model
Cards are PHP-serialized to the DB (`card_serialized` column), so the private `$hasBonus` field persists correctly across requests. Verified by looking at `DB.php` — `serialize()` on save, `safeUnserialize()` on load. The `_01040` (Rena Klingenhalter) card uses the same pattern (event-driven combat modification) but tracks state derivably from attachments rather than a flag. Guillén can't do that since renown is external state.

### Event Triggers — Correct
`handleEvent` correctly re-checks renown on:
- `EventCharacterMustered` / `EventApproachCharacterPlayed` (when Guillén enters play)
- `EventPlayerGainsReknown` / `EventPlayerLosesReknown` (when any renown changes)

## Files Changed
- `modules/php/cards/_7s5s/_01064.php` — rewrote `checkRenown()` loop logic
