# Unyielding Loyalty (01032) — ControllerId on null during engarde

## Crash
```
Attempt to read property "ControllerId" on null
Reaction_01032.php:220 handleEvent(EventCardEngaged|Engarded)
```
Stack through PlunderEnd → DuskBegin → stDuskPhaseCleanup nextState (2440).

## Root cause
Engarde handler used `getCharacterById($event->cardId)`. That returns null for
attachments. Dusk cleanup (`stDuskPhaseCleanup`) engardes engaged attachments
via `createCardEngardedEvent(attachment->Id)`. UL is still in hand → enters
handler → fatal before `shouldReactToEvent`.

Card text is "your cards", not characters only. Attachments also engage/engarde
from abilities (Henri 01065, Yield 02020, Technique_03051, etc.).

## Fix
- Engarde (and Engage): use `getCardById` + `$card !== null` before ControllerId
- Same null guards on Moving / Wound / Heal / Target / Challenge handlers

## WHY dusk won't spam UL after fix
Dusk engarde events have sourceId=0 / empty abilityId. `shouldReactToEvent`
returns false when initiatorId === 0. Fix only stops the fatal; framework
cleanup still does not offer the cancel.

## Side effect (intentional)
Opposing abilities that engage/engarde *your attachment* can now be canceled
by UL instead of crashing. Matches printed "your cards".
