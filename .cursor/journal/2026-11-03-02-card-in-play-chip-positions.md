# Card in-play chip / badge positions

User asked for inventory of badges on cards in play + position table — overlap concerns.

## Scope
Absolute overlays on in-play `._7sfs-card` (110×110).

Filtered out (not on in-play cards / not this concern):
- `_7sfs-number-order-chip` — chooseList selection UI (often hand)
- `_7sfs-hand-wealth-cost` — hand only
- `_7sfs-location-action-used-list` — city location image, not a card
- city renown/control, duel-table chips, scoreboard badges, engaged rotate

Kept `_7sfs-card-player-list` for Siren's Scream / Crabs / Cat's Embargo name — those mount on scheme card `_image` in play.

## Known same-slot / likely collisions
- Challenger + Defender + Yevgeni all `left:80; top:0` (challenger/defender mutually exclusive; Yevgeni can still share that corner — user did not ask to move C/D)
- Crystal Eye (`33,60` 35px) sits under renown (`35,35` 39px) — vertical overlap ~9px if both present
- Cats Embargo BR vs player-color / Indomitable at `80,75` (Indomitable box 75–100; embargo ≈70–105)
- Number-order chip and card-player-list both top-right

## 2026-11-03 reposition (Yevgeni / Maryam / Carmella / Indomitable only)
Stacked on `left:80` at `top` 0 / 25 / 50 / 75 — stride = chip `height` (25). Maryam was 30, Carmella shared 30, Indomitable was 60.

WHY not account for 2px borders in the stride: user asked positions from width/height; borders still nibble ~4px into neighbors visually. Could add `box-sizing:border-box` later if that bothers.

WHY leave Yevgeni at 0 despite C/D: instruction was update only those four; C/D stay put.
