# Rename 7s5snew images to Image property

## Task

Folder `misc/Assets/jpg/image_store/7s5snew` has ~203 named JPGs like `A - Guild Triskelion - CORE.jpg`. Match filename (card title portion) to PHP class `$this->Name`, rename file to `$this->Image`.

## Done

Renamed all 203 JPGs in `misc/Assets/jpg/image_store/7s5snew` to class `Image` values.

## Approach / WHY

1. Scrape Name+Image from `_*.php` under `_7s5s` / `faf` / `tac`.
2. Prefer `_7s5s` / ExpansionNumber 1 when Names collide (filenames are `- CORE`). WHY: FAF reprints share Name (Angeline, Cesca, Yevgeni…) but these arts are CORE pack.
3. Normalize: strip type prefix + CORE/ERRATA/PROMO suffix; `_`→`'`; hyphens→spaces; strip diacritics/apostrophes/punctuation for compare.
4. Two-phase rename (`*.jpg.renaming` → final) to avoid clobber.

## Gotchas that bit the first dry-run

- Name regex `[^"']+` truncated at apostrophes inside `clienttranslate("Kaspar's…")` — use separate `"` / `'` patterns.
- Several Scarpa/scheme cards use bare `$this->Name = "..."` without `clienttranslate` — must parse both.
- Filename aliases: `Salvaggi`→`Slavaggi` (code typo), `Madre Delores`→`"Madre" Dolores`.
- `Well-Equipped`: hyphen must become space before stripping punctuation or "Wellequipped" ≠ "well equipped".

## Special Image overrides (not a single Name→Image)

- Kaspar: CORE→`01047.jpg`, ERRATA→`01047v2.jpg` (class Image is only v2; both files share Name).
- Appealing: CORE→`01159a.jpg`, PROMO→`01159b.jpg` (subclasses set art).
