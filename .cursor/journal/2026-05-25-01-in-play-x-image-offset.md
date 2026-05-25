# InPlayXImageOffset

## What I did

Added `public int $InPlayXImageOffset = 0;` to `modules/php/cards/Card.php` and pushed it into `getPropertyArray()` as `inPlayXImageOffset`.

Changed `._7sfs-card` `background-position` from a static `-52px -36px` to:

```
background-position: calc(-52px + var(--in-play-x-image-offset, 0px)) -36px;
```

The `0px` fallback means cards that don't supply the variable render identically to before.

Plumbed the value through three in-play templates (`jstpl_character`, `jstpl_card_attachment`, `jstpl_card_event`) by adding `--in-play-x-image-offset:${inPlayXImageOffset}px` to their inline style. The matching `dojo.place` calls in `Utilities.js` now pass `inPlayXImageOffset: card.inPlayXImageOffset ?? 0`.

## Why this shape

The property name was the user's: "InPlayX...". That points at characters/attachments/events on the board, not the hidden card backs. `_7sfs-card-back` overrides `background-position` anyway, so the hidden templates would have been a no-op even if I had wired them up.

Used a CSS variable + `calc` rather than rewriting the whole `background-position` from JS so that:
1. The existing `-52px -36px` design is preserved as the baseline — per-card data is just a delta.
2. Cards with no override don't need to pass anything (fallback `0px`).
3. Only the X is parameterized, matching the property's name.

## What's not done

No card actually sets a non-zero offset yet — this is just the plumbing. The user will presumably override `$InPlayXImageOffset` on specific card subclasses where the cropped art needs to shift.
