# City attachment equip fly animation

## Task
User wanted CityAttachment equips to animate the card flying from its city location to the character.

## Approach
Extended `notif_attachmentEquipped` only — no PHP changes.

Detection: `attachment.deckOrigin === 'City'` && not from hand && no `from_character_code` (excludes character-to-character moves).

When the attachment was sitting in a city row (`isCardInCity` + live divId):
- Defer destroying the city-row node until after animation
- Recreate character with attachment attached (normal flow)
- `animateCardToElement(oldAttachment, newCharacter)` in parallel with width collapse (same pattern as locker/discard flies — prevents city-row jump)
- Destroy old node after

When equipped straight from city deck (no in-play element): `animateCardFromElement(character, city-ul-tower)` — same origin as `notif_cityCardAddedToLocation`.

Faction attachments and hand equips keep the existing pop-scale on the character.

## WHY animate the old card, not the character
User said "flies the card from its location to the character". The city-row attachment node is the visible card; flying it into the character (while character already shows the equipped art) matches discard/sink fly semantics elsewhere in Notifications.js.

## Unfinished
Deploy and playtest: equip from same location, Smuggled Item cross-location equip (Home char, Docks attachment), direct deck equip.

## Fix: no shrink during equip fly
User didn't want the card shrinking in transit. Added `preserveScale: true` to `animateCardFromElement` / `animateCardToElement` (translate-only path). Equip uses it. City-row width collapse now runs **after** the fly (was parallel — that also squashed the card mid-flight).
