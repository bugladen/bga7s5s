# Image-only hover ability font size

Eddie: ability labels under the card image (Image-only hover) need ~2pt smaller font.

## Where it lives
- Tooltip HTML: `Utilities.js` `createTooltipForCard` — `_7sfs-card-info` / `_7sfs-card-info-text` under the card img (only when hover type ≠ 2 / Hover Text).
- CSS: `._7sfs-card-info-text` was the effective size (14px); tippy parent `._7sfs-card-info` was 12pt but children overrode it.

## Change
- `._7sfs-card-info-text`: 14px → 12px
- tippy `._7sfs-card-info`: 12pt → 10pt

Both reduced ~2 so parent/child stay aligned. Only used for image-only tooltip overlays (traits/conditions/abilities).
