# City Event Text Tooltips

User report: text-based tooltips don't display for city event cards. They work fine for city characters.

## Root Cause

Two gaps in `modules/js/Utilities.js`:

1. **`createEventCard()` bypassed `createTooltipForCard()`** — it called `addTippyTooltip()` directly with an image-only tooltip. Other card creators (`createCharacterCard`, `createSchemeCard`, `createAttachmentCard`) all route through `createTooltipForCard()`, which is the dispatcher that honors the user's text-vs-image preference (`USER_PREFERENCES_CARD_HOVER_TYPE == 2`).
2. **No `createTextTooltipForEvent`** existed. `createTooltipForCard()` had branches for Character/Scheme/Attachment/Risk but not Event. Same gap appeared in two other dispatchers: the bga-cards stock setup (~line 260) and `setupNewStockCard` (~line 1124).

So even if I'd only fixed (1), the dispatch would have fallen through to the image fallback in text mode.

## Fix

- Added `createTextTooltipForEvent(card, nodeId)` modeled on `createTextTooltipForScheme` (closest analogue — both are city-deck cards). Shows Name, Type, Set, Card #, Traits, Text, and any available abilities (actions/reactions/maneuvers/techniques) struck through if unavailable.
- Routed `createEventCard()` through `this.createTooltipForCard(event)` instead of the inline `addTippyTooltip` call.
- Added `'Event'` branches to all three dispatch sites: `createTooltipForCard`, the bga-cards stock setup block, and `setupNewStockCard`.

## Why this shape

WHY: I deliberately mirrored the Scheme tooltip rather than the Character/Attachment ones because city events don't have stats (combat/finesse/influence, riposte/parry/thrust, etc.) — only abilities and rules text. Schemes are also card-text-driven city cards, so the layout transfers cleanly.

WHY image-only fallback was kept on the non-text path: City events have no `controllerId` (they're city-deck cards owned by no player), so the existing `if (!card.controllerId)` early-return in `createTooltipForCard` already handled the non-text-preference case correctly. Didn't need to touch it.

## Risk / Things I'm not 100% on

- I'm assuming city event cards expose `actions`/`reactions`/etc. on their JS payload the same way schemes do — that comes from `IHasActions`/`IHasReactions` on the PHP side via `getPropertyArray`. Most city events probably don't have these (events are usually triggered effects, not persistent cards with available abilities), so the abilities section will just be omitted in practice. No harm if absent.
- Didn't verify in-browser. Deployment is SFTP-only, so this needs a manual smoke test by the user: hover a city event with text-tooltips preference enabled, confirm the table renders with the card text.

## Follow-up: City Card # row

User asked to add a `City Card #` row directly under `Card #` in every city card type's text tooltip. The relevant types are CityCharacter, CityAttachment, and CityEventCard — these are the only `ICityDeckCard` implementations, which is also the only place `cityCardNumber` gets attached to the JS card payload (via `addCityProperties` in `CityDeckCardTrait`).

Added a conditional spread to the rows array of the three corresponding tooltip functions:

```js
...(card.cityCardNumber ? [row(_('City Card #'), card.cityCardNumber)] : []),
```

WHY a spread instead of a separate `if (...) rows.push(...)` block: keeps the row order obvious by position in the array, doesn't fragment the initial declaration into a four-step build. Empty array spread is a no-op for non-city cards, so this is safe to leave in place even on cards that aren't city deck cards (Scheme/Risk) — though I only added it where it's actually meaningful.

WHY conditional rather than always rendering: schemes and risks aren't `ICityDeckCard`, so `cityCardNumber` is undefined on them. If a future change makes some scheme or risk a city card, the row will appear automatically without further edits.
