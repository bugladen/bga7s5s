# Sturdy Shield OffHand vs Attachment Type Limit (rules Q)

## Question
User equipped Sturdy Shield on a character that already had Leather Spaulders + Mastercrafted Rapier. Game reaction asked them to discard Spaulders or Shield. Is that correct given Offhand text?

## Answer: No — that discard is wrong

Offhand reminder text (and `_04055` / `Reaction_AttachmentTypeLimit` WHY comments):
- Offhand does **not** count against one Armor / one Weapon / one Attire
- Separate limit: one Offhand per character

Legal board state:
- Spaulders = 1 non-OffHand Armor
- Rapier = 1 non-OffHand Weapon
- Sturdy Shield = 1 OffHand (Armor trait ignored for the Armor limit)

`Reaction_AttachmentTypeLimit::getLimitedAttachmentsOfType` already filters `!$attachment->OffHand`. With OffHand true on Shield, `characterExceedsAttachmentLimit` should be false → no reaction.

## If user still saw Spaulders vs Shield buttons

Only way both appear as Armor discard choices is `OffHand === false` on the live Shield instance. Buttons come from `getLimitedAttachmentsOfType('Armor')` when count > 1; OffHand path only fires when 2+ OffHands.

Suspect if repro continues:
- Studio upload missing `$this->OffHand = true` on `_04055`
- Or serialized card blob from an earlier upload without OffHand (constructor not re-run on unserialize) — classic BGA serialized-object trap; new game / recreate deck after upload

`_04055.php` still untracked in git at time of this note; local file does have OffHand=true.
