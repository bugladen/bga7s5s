# Remove OLD_CATS_EMBARGO_TARGET

User asked to strip every reference to the legacy condition constant.

## Why it existed

`CATS_EMBARGO_TARGET` was renamed from `"Cats Embargo Target"` to `"Cat's Embargo Target"` (apostrophe). `OLD_CATS_EMBARGO_TARGET` kept the pre-rename string so in-progress BGA tables that already stamped the old value would still trigger Reaction_01098 and still get locker cleanup / UI chips.

## Why remove it now

User wants it gone — no dual-check anymore. Live games with the old stamp string will no longer match; only `"Cat's Embargo Target"` counts. That is intentional per this request (compatibility shim no longer needed).

## What changed

- Dropped the constant from `Game.php`, `FakeGame.php`, `seventhseacityoffivesails.js`
- `_01098` locker cleanup and `Reaction_01098` play/discard gates: `CATS_EMBARGO_TARGET` only
- Setup chip + Notifications filter: same
- Deleted the "honors the legacy embargo stamp" reaction test and the locker legacy-stamp asserts in `Card_01098_Test`

Regression: Card_01098 + Reaction_01098 — 40 passed.
