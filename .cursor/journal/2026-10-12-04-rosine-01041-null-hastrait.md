# Rosine (_01041) — null hasTrait on EventCardMoved

## Crash

Server: `Call to a member function hasTrait() on null` at `_01041.php:156` during `EventCardMoved` handling (fromLocation branch). Stack: Theah::runEvents → card handleEvent.

## Root cause

`EventCardMoved` fires for **any** card move, including attachments. Handlers did:

```php
$character = $event->theah->getCharacterById($event->cardId);
if ($character->hasTrait("Sorcerer") && ...)
```

`getCharacterById` returns null for non-characters — same bug already fixed on `_01071` and `_01119`. Crash path: attachment leaves Rosine's city location → fromLocation match → null→hasTrait.

toLocation branch had the same ungarded call; Mustered/Destroyed/Recruited also lacked guards (those events should always be characters, but cheap insurance).

## Fix

`$character &&` before every hasTrait/Location use after getCharacterById. WHY comment points at attachment EventCardMoved.

Did **not** change influence aura math or home short-circuit from `2026-05-07-03-rosine-01041-dusk-influence-bug.md` — those are separate.

## Test

`Card_01041_Test`: fire EventCardMoved for `_01073` (attachment) from Rosine's docks → home; assert no influence event and no fatal.
