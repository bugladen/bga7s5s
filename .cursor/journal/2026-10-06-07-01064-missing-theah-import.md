# Guillén (_01064) missing Theah import — Fatal error

## Report
BGA log 06-Oct-2026 02:45 Asia/Beirut:
`Could not check compatibility between _01064::onAbilitiesBlanked(...cards\_7s5s\Theah)`
and `Character::onAbilitiesBlanked(...theah\Theah)` — class `cards\_7s5s\Theah` not available.

## Cause
Fate's Silence gap-fill (2026-10-05, commit e1aea91b) added `onAbilitiesBlanked`/`onAbilitiesUnblanked(Theah $theah)` to Guillén without `use ...\theah\Theah`. PHP resolves bare `Theah` to the file's namespace `cards\_7s5s\Theah`.

## Fix
Added the missing import. Scanned all other `onAbilitiesBlanked(Theah` overrides under `modules/php/cards` — only `_01064` was missing it.

## WHY easy to miss
Sibling cards already imported Theah for other reasons, or got the import when hooks were added. Guillén's prior code only used `Game` / event classes, so the new type hints had nothing to resolve against until runtime load.
