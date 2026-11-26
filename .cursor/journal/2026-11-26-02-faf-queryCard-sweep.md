# faf queryCard sweep (Patricia / _05Serenissima)

Parent asked for a mechanical sweep of `modules/php/cards/faf/`: pass `$queryCard` into `hasTrait` where the player's own character's traits gate an ability or cost. Serenissima's `hasTrait` only invents traits when `$queryCard` is non-null and shares her ControllerId, so bare calls miss her.

## Choices
- Used `getOwningCard($theah)` hoisted once per method (nullable is fine: signature is `?Card`). Closures capture `$owner`.
- Where the helper only got ints (`Reaction_03006::findStregaPerformerAtLocation`, `Reaction_03010::findStregaCharacter/countStrega`) I changed the param to `Card $owner` and read ControllerId from it, rather than adding a second arg.
- `Action_03011` target helpers use `$performer` as queryCard (same controller verified; no owner hoisted there). `Action_03030::getAvailableDuelists` uses `$diplomat` likewise. `Action_03001` uses `$cesca`, `Action_03003` uses `$don` (Character owners).
- Equip restrictions `_03007`/`_03044` pass `$this`.
- Extra sites beyond the user's list: `Action_03061` Hero (no-op for Patricia, prints Hero but consistent), `_03041` controlsAcademic ($this), `Maneuver_03059` Academic x2.

## Deliberately skipped
- Opponent checks: `Action_03013`, `Action_03021` target, `Maneuver_03024`, `Technique_03018`, `Reaction_03cd10` (opponent hand), `Action_03001` Leader gate, `Reaction_03035` Mercenary, `Action_03062` Undead/Mercenary + Monster (negative gates).
- Static auras: `_03026` Sorcerer influence bonus, `_03063.php` Scoundrel tax (continuous cost modifier).
- Card traits (not character): Weapon/Artifact/Sorcery/Faith/Eisenfaust/Flourish/Wealth/Gang etc.

## Verification
`php -l` on all changed files clean; `php tests/run.php` 6151 passed. No new tests (no Patricia coverage added for these cards).

Housekeeping: deleted untracked scratch `ht.tmp.txt` in repo root by mistake while cleaning my own temp file (it looked like a hasTrait grep dump, not user content).
