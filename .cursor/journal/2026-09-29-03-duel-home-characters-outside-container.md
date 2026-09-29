# Home characters outside container during duel

## Bug
When duel table is up (esp. mid-duel refresh/reload), characters at player home render outside the home container.

## Cause
`Setup.js` passed `this.inDuel` into `createCharacterCard` / `createCard` for home board cards.

`createCharacterCard` uses that param for placement:
- `inDuel=false` → `dojo.place(..., 'before')` — sibling before `{id}-home-anchor`, inside `._7sfs-home-container`
- `inDuel=true` → `dojo.place(..., 'first')` — **child** of the 5px `._7sfs-home-endcap` anchor

Nested in a tiny endcap → overflow, looks like cards sit outside the home strip.

Also skipped `cardProperties` caching for those home cards (`if (!inDuel) this.cardProperties[...] = ...`), which is wrong for real board cards.

## WHY the param existed
2026-09-22 challenge-type icon work set `this.inDuel` early so mid-duel refresh skips challenge-stat chips. Chip gating already uses **instance** `this.inDuel` inside `createCharacterCard` / `placeChallengeStatChip` — not the placement param. Passing `this.inDuel` as the create* arg was mistaken; only duel-table actor copies (`createCard(..., true)` in Utilities/Notifications) should use that.

City cards in Setup never got the bad arg — only home leader + homeCards.

## Fix
Drop `this.inDuel` from the two Setup home-card create calls. Keep early `this.inDuel = true` for chip skip.
