## Home deck-count icon

User asked for a new home-location icon between crew-cap and discard: deck count. Same boardResources sprite sheet as discard, CSS `background-position: -342px 0`, same 36×24 + 50% background-size as discard, white text like crew-cap.

### WHY these CSS choices
- Discard uses `background-size: 50%` of the sprite vars, so CSS `-342px` maps to native ~684px — that's the isometric card-stack with a solid black top (good for white digits). Native pixel 342 is the stone/chest area — wrong if you crop the PNG directly.
- Font 12pt / line-height 24px (not crew-cap's 16pt/27px) so two-digit counts fit the shorter 24px discard-sized box.

### Wiring
- Template: `${id}-deck-count` between crewcap and discard.
- `getAllDatas` now sends `player.deckCount` via `countCardsInLocation(Faction-$id)`.
- `createHome` reads `gamedatas.players[id].deckCount`.
- `playLeader` fires *before* faction cards are inserted — so deckCount is computed from the deck definition and passed in the notif; client stores it before `createHome`.

### White gap fix
User screenshot showed a white rectangle to the right of the deck icon. Cause: element width matched discard (36px) but the isometric deck glyph only inks native 684–739 (~28px at 50% scale). The extra 8px was blank sprite background. Fixed width to 28px. Keep height 24 to match discard row.

### Slightly larger
User wanted a few more pixels. Bumped to 32×28 with background-size 0.57 and position -390 (native 684 × 0.57). WHY rescale position with size: CSS bg-position is in the scaled image's coordinate space, so keeping -342 at a larger scale would show the wrong glyph.

### Text centering
Count sat a few px left of the black top. Geometric center ≠ visual center on the isometric stack. `padding-left: 4px` + `box-sizing: border-box` nudges centered text right without changing the outer box or shifting the sprite.

### Spacing
Crew-cap → deck gap cut by ~5px: deck `margin-left` 5 → 0. Discard keeps its 5px so deck→discard spacing unchanged.

### Live deckCount updates
Wired after user asked. Approach: absolute `factionDeckCount` notif (not deltas) from central choke points.

**DeckTrait**
- `notifyFactionDeckCount` / nestable begin/end batch
- `insertCardOnPlayerFactionDeckExtreme` — insert + notify
- `moveCard` / `moveCardInDeck` — if old or new location is `Faction-*`, notify (covers AddedToHand from deck, gamble→dueling line, parkCard out of deck, discard reshuffle moves)
- `playerDrawCard` — batched so reshuffle+pick is one final count (pickCard bypasses Game::moveCard)
- shuffle — batched so N discard moves → one notif
- planning panache draw loop — outer batch

**EventHub** EventCardAddedToFactionDeck uses the insert helper.

**Hand sinks that bypassed events:** Reaction_03006 / 03007 now use the helper.

**JS:** `notif_factionDeckCount` → `updateFactionDeckCountDisplay`.

Reorder-only extreme inserts (gamble unchosen, look-order) left on raw insert — size unchanged, count stays correct without a notif. If a future size-changing path uses raw `$deck->insertCardOnExtremePosition` onto Faction-* without the helper/moveCard, count will go stale — prefer the helper.
