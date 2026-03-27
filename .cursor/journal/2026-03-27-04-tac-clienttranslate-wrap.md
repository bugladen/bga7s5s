# TAC Card clienttranslate() Wrapping

## What Was Done

Wrapped `Title` and `Traits` values in `clienttranslate()` calls across all card classes in `modules/php/cards/tac/`.

### Title wrapping (7 character files)
- _02001, _02002, _02003, _02011, _02012, _02013, _02021

### Traits wrapping (19 files)
- _02001 through _02021 (excluding _02008_RiskClone and _02015)

### Skipped files
- `_02008_RiskClone.php` — Has no Title or Traits assignments in constructor (it's a programmatic clone, sets FaceDown = true only)
- `_02015.php` — Has empty Traits array `[]` and no Title (it's a Scheme)

## WHY

BGA's translation system uses `clienttranslate()` to mark strings for extraction by the translation pipeline. Without it, Title and Trait strings would never appear in the translation catalog and would remain untranslated for non-English players.

The `Name` and `Text` properties were already wrapped in all files. Title and Traits were missed.

## Files Modified
All `_020XX.php` files in `modules/php/cards/tac/` except `_02008_RiskClone.php` and `_02015.php`.
