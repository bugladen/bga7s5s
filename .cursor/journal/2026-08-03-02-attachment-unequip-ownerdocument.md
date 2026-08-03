# attachmentUnequipped ownerDocument crash

## Symptom
`dojo.place` in `notif_attachmentUnequipped` threw
`Cannot read properties of null (reading 'ownerDocument')`. Stack from type-limit
discard testing after Reaction_AttachmentTypeLimit.

## Root cause
Handler destroy/recreated the *character* via
`dojo.place(placeholder, character.divId, 'before')`. Stale/missing character
node → dojo.byId null → ownerDocument crash.

## Fix (take 2 — Eddie wants visible unequip + discard fly)

Keep character in-place updates (stats, `--attachment-count`, indices). Do **not**
destroy/recreate the character.

After detaching, re-create the attachment as a city-row sibling (`attachedToId`
already null → `createAttachmentCard` uses placement `before`, no absolute
`_7sfs-attached-card`). Anchor on live character.divId; fall back to location
endcap if character DOM is gone.

`notif_cardDiscardedFromPlay` now `animateCardToElement` toward
`${playerId}-discard` (same fly+shrink as city discard). Shrink-in-place only
as fallback when discard icon missing.

WHY recreate attachment but not character: Eddie wants to see the card in the
city briefly, then watch it fly to discard. Character recreate was the crash
vector and wasn't needed for that UX.

## Related
- 2026-07-01-01-breastplate-unequip-notif-crash.md
- 2026-04-19-01-unequipped-attachment-positioning.md (detached sibling CSS still applies)
- 2026-08-02-03-attachment-type-limit-reaction.md
