# City attachment equip fly snap-back

## Bug
`notif_attachmentEquipped` for a city-row attachment: card flies to the character, then briefly reappears in the original city slot, then disappears.

## Cause
`animateCardToElement(..., { preserveScale: true })` used WAAPI without `fill: 'forwards'`. When the animation ended, the element snapped back to its layout rect (still in the city row) before the width-collapse + destroy ran. That snap *is* the "reappears then vanishes" flash.

WHY this only showed up on equip: discard/locker flies shrink + lower opacity and destroy immediately — snap is hard to notice. Equip deliberately keeps full size/opacity (`preserveScale`), so the snap is obvious.

## Fix
1. `Utilities.js` `animateCardToElement` preserveScale path: `fill: 'forwards'` so the card stays at the character after the fly.
2. `Notifications.js`: set `opacity = 0` on the city node after the fly, before width collapse — otherwise the forwards-filled ghost sits on the character during the 150ms slot collapse.

## Related
Continues `2026-08-29-02-city-attachment-equip-animation.md`. Did not change the shrink-scale discard path (no fill:forwards there) — leave that alone unless someone reports the same snap.
