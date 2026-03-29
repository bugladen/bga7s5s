# Renown Not Showing on Cards After Page Refresh

## The Bug
Renown displayed on cards showed correctly in real-time via `notif_reknownUpdatedOnCard`, but disappeared on page refresh.

## Root Cause
Most `create*Card()` functions in `Utilities.js` never created a renown chip during setup. Only `createEventCard()` had the logic:
```javascript
if (event.reknown > 0) {
    dojo.place(this.format_block('jstpl_reknown_chip', { ... }), `${event.divId}_image`, 'last');
}
```

The notification path worked because `notif_reknownUpdatedOnCard` dynamically creates the chip regardless of card type. But the setup path (page refresh) relies on each `create*Card` function to render the initial state.

## Fix
Added renown chip creation to three functions that were missing it:
- `createSchemeCard()` — after city/non-city styling, before tooltip
- `createCharacterCard()` — after condition chips (wounds, etc.), before engaged check
- `createAttachmentCard()` — before engaged check, before tooltip

All use the same `jstpl_reknown_chip` template and `${divId}-reknown` ID convention.

Skipped `createHiddenCard` and `createHiddenAttachmentCard` — face-down cards don't have a visible `_image` div to attach renown to.

## WHY this pattern
The `${divId}-reknown` ID must match what `notif_reknownUpdatedOnCard` uses (`${card.divId}-reknown`) so that subsequent real-time updates correctly find and replace the chip created during setup.
