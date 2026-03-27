# clienttranslate() Wrapping for Title and Traits

## What Was Done

Wrapped all `$this->Title` assignments and all `$this->Traits` array entries in `clienttranslate()` across 203 files in `modules/php/cards/_7s5s/`.

Final counts:
- 64 Title properties with clienttranslate (61 newly added, 3 already wrapped: _01006, _01037, _01095)
- 514 Traits entries with clienttranslate (512 newly added by script, 2 manually fixed)

## WHY

BGA framework uses `clienttranslate()` to mark strings for extraction by the translation pipeline. The Name and Text properties were already wrapped, but Title and Traits were bare strings. Without wrapping, these strings wouldn't appear in translation files and would stay in English for all locales.

## Gotcha: Apostrophes in Trait Names

The automated regex `[^"']+` rejected ALL quote characters inside strings, which meant `"Explorer's Society"` (double-quoted string containing an apostrophe) was silently skipped by the script. Found 2 files affected: `_01180.php` and `_01185.php`. Fixed those manually.

**Future-proofing note**: If adding new trait names with apostrophes (like `"Explorer's Society"`, `"Knight's Order"`, etc.), the same kind of regex-based tooling could miss them. The fix is straightforward — just use double quotes and make sure `clienttranslate()` is applied.

## Files Not Touched

- Subdirectory files (`actions/`, `reactions/`, `techniques/`, `maneuvers/`) — none have Title or Traits
- `_01159a.php` — extends `_01159` and has no Title or Traits of its own
