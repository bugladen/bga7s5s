# Tijani _04cd29 City Action available without control

## Bug
User: Tijani's action shows for players who don't control her.

## Cause
`Action_04cd29::isAvailableToPlayer` gated only `cardInCity` + En Garde + eligible targets. No controller gate.

`CardAction::isAvailableToPlayer` only rejects when the owner *is* controlled by someone else:
```php
if ($owner->isControlled() && $owner->ControllerId != $playerId) return false;
```
Uncontrolled city-deck mercs pass for **every** player. So while Tijani sat unmustered in the city, her En Garde City Action was offered to whoever's turn it was.

## WHY the original code skipped isControlled
`2026-07-25-01-04cd29-tijani.md` deliberately chose `cardInCity` over `isControlled`, thinking City Action = works unmustered and `isControlled` is only for printed Action. That distinction is wrong for **character** City Actions.

## Fix (final)
Match the other CityCharacter CharacterActions: `$owner->isControlled()` before `cardInCity`.
- `isControlled()` blocks the uncontrolled-for-everyone leak
- CardAction parent then restricts to the controlling player (`ControllerId != $playerId`)

Tried upgrading all 9 to explicit `ControllerId != $playerId`; user preferred leaving the other 8 alone and having Tijani use the same `isControlled()` pattern. Reverted those eight.

## Siblings already correct with isControlled()
Astrid `04cd04`, Penya `03cd01`, Kaj `01180`, Gustavo `01192`, Adelheide `01194`, Kalla `01197`, Ravenna `01201`, Giacinto `01205`.

## Do not regress
Do **not** drop `isControlled` for "City Action works unmustered" — character City Actions need it. Create-city-character skill pattern-c still soft-sells unmustered City Actions; believe Kaj/Penya over that sentence.
