# Smuggled Item (01187) — citywide equip fix

## Context
User clarified printed intent: equip from hand **or any available attachment at any City location**, not only at the performer's location. Example: character at Home can Smuggled Item-equip a City attachment sitting at the Docks.

Previous audit (2026-04-09) treated "a City location" as performer's location — wrong for this card.

## Changes
- Added `Theah::getAvailableAttachmentsInCity()` — unions `getAvailableAttachmentsAtLocation()` across all city locations (same "available" = unattached Attachment semantics).
- `Action_01187::isAvailableToPlayer` uses city-wide list.
- `ArgumentsTrait` choose-location / choose-from-play args branch on `SMUGGLED_ITEM_EQUIP_TYPE` to expose all city attachments (including when performer is Home).
- `FrameworkActionsTrait` selection + confirm validation for Smuggled Item checks city-wide availability instead of performer location match.

## WHY not filter isControlled on attachments
User said "non-controlled" but game term "available attachment" is already defined as unattached via `getAvailableAttachmentsAtLocation`. City deck attachments at locations are uncontrolled when available. Didn't add extra `!isControlled()` filter — would be redundant unless we discover controlled-but-unattached edge cases.

## Unfinished
None — deploy and playtest Home → Docks equip path.
