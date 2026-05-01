# Conditions on Card Tooltips

User asked: conditions on a card should display on both image and text tooltips, refresh when added/removed via notifications, and survive a page refresh.

## What "conditions" are

`card.conditions` is a flat array of strings — the same string constants defined in `modules/php/Game.php` (e.g. `"Adversary of Yevgeni"`, `"Indomitable Will Condition"`, `"Challenger"`, `"Defender"`, etc.). On the JS side they're aliased on the main game object as `this.ADVERSARY_OF_YEVGENI` etc. (declared in `seventhseacityoffivesails.js` ~line 141).

## Where conditions live and how tooltips attach

Three different placements, three different tooltip element ids — this is the trap. I had to handle all three in one refresh helper:

1. **In-play characters / attachments / events / schemes**: tooltip attached to `${card.divId}_image`. Built by `createTooltipForCard(card)` in `Utilities.js`.
2. **Approach deck (legacy ebg.stock)**: tooltip attached to the stock div id (`approachDeck_item_X`). Built by `setupNewStockCard`. The card has no `divId` for this case.
3. **Faction hand (bga-cards CardManager)**: tooltip attached to `${cardElement.id}_front`. Built inline in `addCardToDeck` → `applyFactionHandCardStyle`. `card.divId` here is `factionhand-card-${id}`, NOT what the tooltip is attached to.

The conditions that mattered for which placement:
- ADVERSARY_OF_YEVGENI, MARYAM_BENU_*, CARMELLA_ABILITY_USED, INDOMITABLE_WILL_CONDITION, CHALLENGER, DEFENDER → in-play characters
- CRYSTAL_EYE_TARGET → approach deck
- CATS_EMBARGO_TARGET → faction hand

## Approach

1. Added `conditionsRow(card, row)` and `buildImageTooltipHtml(card)` helpers in Utilities.js. Every text-tooltip builder (Character/Scheme/Attachment/Event/Risk) and every image-fallback path now reads `card.conditions` consistently.
2. Added `refreshTooltipForCard(card)` — a single dispatch that figures out which of the three placements the card lives in (by probing `${divId}_image`, then factionHand.getCardElement, then approachDeck.getItemDivId). It calls `createTooltipForCard` or `_applyTooltipToNode` accordingly.
3. After every `card.conditions.push(...)` / filter in `Notifications.js`, called `this.refreshTooltipForCard(card)`.

## Why this shape

WHY a single `refreshTooltipForCard` instead of inlining the right tooltip rebuild in each notif handler: the placement of any given condition can change over time (cards move between locations), and adding a new condition later shouldn't require rediscovering the placement-specific tooltip wiring. One function, one dispatch.

WHY conditions row only appears when non-empty: keeps the tooltip table tight when there are no conditions (the common case). The row label `Conditions` is only meaningful when a value follows.

WHY image-mode conditions are displayed via a red badge overlay on top of the image: it mirrors the existing card-info overlay (gold for abilities, white for traits) so the visual language is consistent. Red flags it as state-on-the-card rather than intrinsic-to-the-card.

WHY page refresh works without any extra wiring: gamedatas already includes `card.conditions` from the server (PHP cards persist conditions and serialize them via `getPropertyArray`), and tooltips are built from that data on initial setup via the same builders. Once the builders read `card.conditions`, refresh handles itself. Setup.js was already using `card.conditions.includes(...)` to restore the chip badges on the cards themselves, which confirmed the data was already there.

## What I deliberately didn't change

- The chip badges (the small colored circles placed on the card itself for active conditions) — those already worked correctly across notifications and refresh; the user's complaint was specifically about the hover tooltip text, not the chips. Left them alone.
- Notifications still set the per-chip tooltip (`addTippyTooltip(chipId, ...)`) for hovering directly over the chip itself. That's a different element from the card's main tooltip, and serves a different purpose (label-the-chip vs full-card-summary).

## Risk

- Didn't verify in-browser. Deploy is SFTP-only, so this needs a manual smoke test by the user: trigger a Crystal Eye target / Cat's Embargo target / Yevgeni Adversary / duel and confirm:
  1. The Conditions row/badge shows up in the tooltip immediately after the notification.
  2. After page refresh mid-game, the same Conditions are still on the tooltip.
  3. After the condition ends (challenge cancelled, target cleared), the row disappears without needing a refresh.
- The `_applyTooltipToNode` fallback chain assumes `factionHand` and `approachDeck` are initialized by the time a notif fires. They are, in normal play, because conditions only get applied after setup. If a condition were applied during a setup race, the refresh would silently skip — but the initial tooltip build would catch it on next render anyway, so this is at worst a one-frame delay, never a permanent bug.
