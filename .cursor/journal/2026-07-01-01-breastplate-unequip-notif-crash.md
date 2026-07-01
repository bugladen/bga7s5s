# Breastplate unequip notification crash

## Context

Eddie hit a client crash when `_01153` destroy (lines 101–102) is enabled: unequip + `createCardDiscardedFromPlayEvent`. Stack pointed at `notif_attachmentUnequipped` → `createAttachmentCard` → `format_block` null `.toString()`.

With discard commented out, only unequip runs and no crash — which initially made me think ordering was inverted. BGA docs say notifications run in PHP generation order (unequip then discard), but the crash only happens when discard is in the batch, which strongly implicates `cardDiscardedFromPlay` nulling `divId` on the shared `cardProperties` object before/during unequip handling.

## WHY the fix is in JS not PHP

PHP event order (unequip then discard) is correct and matches Action_01174 / _01050 patterns. The bug is client-side: `notif_attachmentUnequipped` recreates the attachment element even when the next notification removes it from play. That recreation is pointless for destroy flows and fragile when `divId` is already null.

Skipped attachment recreate when `location === Player Discard`; added `createCardId` fallback otherwise. Flows that leave a detached attachment in play (02055 journal case) still recreate because location isn't discard yet.

Also removed debug `console.log('0'/'1'/'2')` that were in the handler.

## Unfinished / note for future

_01153 still missing `initializeFaction('Castille')` like other Castille attachments — unrelated to this crash, default Neutral from Card base constructor.
