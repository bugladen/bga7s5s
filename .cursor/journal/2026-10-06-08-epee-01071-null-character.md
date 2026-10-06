# Épée Sanglante (_01071) — ControllerId on null (EventCardMoved)

## Report
BGA fatal 05-Oct-2026 22:48 America/Chicago:
`Attempt to read property "ControllerId" on null` at `_01071.php:71`
Stack: EventCardMoved → handleEvent → Theah::runEvents

## Status
**Not fixed.** Line 70-71 still:
```php
$character = $event->theah->getCharacterById($event->cardId);
if ($character->ControllerId == $this->ControllerId && $character->hasTrait("Musketeer"))
```
`getCharacterById` returns null for non-Character cards (attachments, risks, etc.). Any EventCardMoved while the scheme is at LOCATION_PLAYER_HOME hits this and fatals.

## Same class of bug already fixed elsewhere
- Nazem `_01119`: `$movedCharacter &&` guards (2026-03-26 audit)
- Unyielding Loyalty Reaction_01032: getCardById + null guard (2026-09-24)

## Fix (applied 2026-10-06)
Guard: `if ($character && $character->ControllerId == $this->ControllerId && $character->hasTrait("Musketeer"))`
WHY comment in code explaining non-character EventCardMoved.

## WHY this fires
Influence aura only cares about Musketeer character moves. Listening to all EventCardMoved without filtering non-characters is the bug. Schema is at home so the outer Location check passes for the whole game after reveal.
