# Splay attachments when a highlighted character is selected

## Bug
Clicking a character highlighted to be chosen (`_7sfs-selectable` on `${divId}_image`) did not splay its attachments.

`splayAttachments` is on the character wrapper. It bails when `event.target` has `_7sfs-selectable`. That guard exists so the choose-click and the splay-toggle do not both run: the toggle would close the stack on the same click that just opened it.

## Fix
`onCardInPlayClicked` calls `openAttachmentSplay` only when this click newly selects the card (`selectedThisClick`). Add-class, not toggle, so a second select (or an already-open stack) stays open. Deselect does not collapse.

The selectable bail in `splayAttachments` stays. WHY: bubble order is image handler first, then the wrapper. If the wrapper still toggled, it would undo the open.

`toggleAttachmentSplay` is the old toggle body, used by the non-selectable click path only.
