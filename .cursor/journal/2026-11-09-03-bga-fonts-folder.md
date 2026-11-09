# BGA fonts folder location

User asked where fonts go per current BGA docs.

Answer from Studio file reference overview (sidebar Game File Reference): **`fonts/`** at game root — listed as "fonts/ - game fonts", sibling of `img/`, `sounds/`, `modules/`, `misc/`.

WHY note this: older practice often stuffed fonts under `img/`; docs now call out a dedicated top-level `fonts/` directory. This repo currently has no `fonts/` dir (only `img/`). No dedicated Fonts wiki page found (Fonts URL 404); overview listing is the doc signal.

## How to reference in CSS (follow-up)

Official migration note is Q1 2026 Studio newsletter (thoun, forum t=48516), not a complete wiki page yet:
- Put files in top-level `fonts/`
- Framework **preloads and declares** them automatically
- Migration: move out of `img/`, **remove `@font-face`**
- Linked wiki `Game_fonts:_fonts_directory` is currently **404** (same for sounds dir page from this machine) — so no published detail on the exact `font-family` string the auto-declaration uses
- Pre-release checklist only: freeware + license `.txt` beside the font files in `fonts/`
- Old cookbook still shows manual external `@font-face` URLs — superseded for local game fonts

Local fonts: `misc/Assets/fonts/` copies + game-root `fonts/` already has `PirataOne-Regular.ttf` + `OFL.txt`.

## `_7sfs-card-stat-box` → PirataOne-Regular

Set `font-family: PirataOne-Regular, serif` on `._7sfs-card-stat-box`.

## Font not picked up — fix

User: duel table / stats weren't using Pirata. TTF name table: family=`Pirata One`, PostScript=`PirataOne-Regular`. Newsletter auto-declare alone wasn't enough (wrong family and/or Studio not injecting yet; dedicated wiki page still 404).

Fix: explicit `@font-face { font-family: PirataOne-Regular; src: url('fonts/PirataOne-Regular.ttf') ...}` at top of CSS. WHY keep CSS name as `PirataOne-Regular` (not `"Pirata One"`): matches existing rules; `url('fonts/...')` mirrors `url('img/...')`. Newsletter said remove @font-face after migrate — override that until auto-declare is verified in Studio. Reminder: `fonts/` must be SFTP'd to Studio too.

## Icon number chips

Grouped rule after `@font-face` for numbers-on-icon sprites: renown, threat, duel-wound, wealth-cost, wound, combat-chip/chip-value, panache, hand-count, home-deck-count, score-renown, crew-cap. Stat boxes already had Pirata. Skipped number-order (plain red badge, not an icon).

