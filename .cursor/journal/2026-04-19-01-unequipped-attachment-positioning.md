# Unequipped Attachment Positioning Bug

## The Bug

When `notif_attachmentUnequipped` fires (e.g., Technique_02055 sinking Dame of Swords), the detached attachment element appears at the far left of the city row instead of in front of the character it was just unequipped from.

## Root Cause

`createAttachmentCard` in `Utilities.js` applied the `_7sfs-attached-card` CSS class based on `controllerId` being truthy. That class has `position: absolute; left: calc(var(--attachment-index) * -15px)` — designed for overlaying attachments on top of their parent character div.

The problem: `notif_attachmentUnequipped` sets `attachedToId = null` before recreating the card. The card is placed as a **sibling** in the city row (via `placement = 'before'`), not as a **child** of the character div. But `controllerId` is still set, so it still gets `position: absolute`. Without a positioned parent container, it resolves against the city row → `left: 0` → far left.

The two properties diverge during unequip:
- `attachedToId` — cleared to null (correctly reflects detachment)
- `controllerId` — still set (correctly reflects player ownership)

The CSS class was keyed off the wrong one.

## Fix

`Utilities.js:1006` — gate `_7sfs-attached-card` on `attachedToId` instead of `controllerId`. Wealth cost hiding still uses `controllerId` since that's about ownership, not attachment state.

```javascript
if (attachment.controllerId)
{
    if (attachment.attachedToId)
        dojo.addClass(divId, '_7sfs-attached-card');
    dojo.addClass(`${divId}_wealth_cost`, 'hidden');
}
```

## Scope

This isn't 02055-specific. Any of the ~20 callers of `createAttachmentUnequippedEvent` would hit the same visual bug if the attachment had a `controllerId` at unequip time. Most unequip flows are followed immediately by `cardRemovedFromPlay` (which destroys the element), so the misplacement may have been too brief to notice before.
