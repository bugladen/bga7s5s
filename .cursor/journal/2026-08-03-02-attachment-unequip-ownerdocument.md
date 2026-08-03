# attachmentUnequipped ownerDocument crash

## Symptom
`dojo.place` in `notif_attachmentUnequipped` (Notifications.js:581) threw
`Cannot read properties of null (reading 'ownerDocument')`. Stack from type-limit
discard testing after yesterday's Reaction_AttachmentTypeLimit work.

## Root cause
Handler always destroy/recreated the character via
`dojo.place(placeholder, character.divId, 'before')`. That requires a live DOM
node. When `character.divId` is null or stale (node already gone), dojo.byId
returns null → ownerDocument crash.

WHY the old recreate path was fragile:
- Multi-attachment death batches: unequip N destroys character DOM, recreate can
  fail mid-batch → later unequips hit stale divId
- Breastplate fix (2026-07-01) only skipped *attachment* recreate when location
  was already Player Discard — but at unequip time location usually isn't discard
  yet, and city-discard path never matched. Didn't guard character.divId at all
- Discard/sink/locker always queue remove right after unequip, so recreating
  character + briefly placing the attachment was pointless work

## Fix
In-place update instead of destroy/recreate:
1. unattachCard + clear attachment DOM
2. If character element missing → return (data already updated)
3. Else patch resolve/combat/finesse/influence, `--attachment-count`,
   `--attachment-index` on remaining attachments, drop attachment-container class
   when empty

WHY in-place over "just guard dojo.place": guarding would still leave the
fragile recreate path for the common discard case. In-place matches what the
client actually needs and covers AttachmentMoved (unequip then equip recreates
on the destination).

## Related
- 2026-07-01-01-breastplate-unequip-notif-crash.md (partial, location-gated)
- 2026-04-19-01-unequipped-attachment-positioning.md (detached sibling CSS —
  no longer relevant if we don't recreate detached on unequip)
- 2026-08-02-03-attachment-type-limit-reaction.md (trigger for this report)
