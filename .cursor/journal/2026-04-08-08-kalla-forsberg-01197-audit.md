# Kalla Forsberg (01197) Audit

## Card Text
> Negotiable (You may parley when paying for this card.)
> When Kalla equips an attachment, it has -1 cost.
> **Action:** Move an equipped attachment between two of your characters at this location.

## Bug 1: Action available when no valid destination exists

`isAvailableToPlayer` only checked that at least 1 friendly character with attachments existed at Kalla's location. It did not verify there was a second friendly character to serve as the destination. If only one friendly character was at the location (e.g., just Kalla with an attachment, no other friendlies), the action would appear available but the player would hit a dead end at the destination selection step (state 01197_3 would offer an empty target list).

The misleading comment said "at least two other friendly characters" but the check was `< 1`.

Fix: Split into two checks — `count(friendlyCharacters) < 2 || count(friendlyWithAttachments) < 1`. Need at least 2 friendly characters at the location AND at least 1 of them must have an attachment.

## Bug 2: Missing source != destination validation

In `actFromActionWithId` state 01197_3, the code validated that the destination character was friendly and at Kalla's location, but never checked that the destination wasn't the same character as the source. While the UI args filter this out, a crafted request could bypass it.

Fix: Added check `if ($id == $fromCharacterId)` before the action resolves. Also cleaned up a duplicate `$fromCharacterId = $game->globals->get(Game::CHOSEN_PERFORMER)` fetch that existed further down.

## Everything else checked out
- Negotiable: `$this->Negotiable = true` ✓
- Equip discount: `getEquipDiscount` checks `$performer->Id == $this->Id`, gives -1 when Kalla is the character receiving the attachment. No type filter (any attachment, not just Artifacts). Correct per card text. ✓
- Stats: Resolve 4, Combat 2, Finesse 1, Influence dashed, WealthCost 4 ✓
- Traits: Mercenary, Vesten ✓
- Action is a regular Action (not City Action) — no city-specific prereqs needed, matches card text ✓
- State flow: 01197 (choose source with attachments) → 01197_2 (choose attachment) → 01197_3 (choose destination) → EVENTS ✓
- Back transitions at each step ✓
- EventAttachmentMoved handler: properly unequips from source, equips to dest, handles death check on source if losing equipment drops resolve below wounds ✓
- Source character validation in 01197: friendly, at Kalla's location, has attachments ✓
- Attachment validation in 01197_2: exists, is on chosen character ✓
- Destination args in 01197_3: excludes source character, friendly only ✓

## Files Changed
- `modules/php/cards/_7s5s/actions/Action_01197.php` — fixed availability check, added source!=dest validation, removed duplicate global fetch
