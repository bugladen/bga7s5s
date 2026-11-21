# Meeting of the Minds — offer on opponent pressure

## Bug
User: MotM out, Academic at location, opponent Pack Tactics — reaction never offered (even though using it might not have swung the pressure).

## Root cause / WHY the old gate was wrong
`Reaction_04035` required `$event->playerId == owner->ControllerId` ("you are pressuring"). That came from reading "your performer's location" as "you are the pressuring player."

Correct reading (user + Loyal/Wardog shape): performer = your Academic at the pressured location. "+1 to your total" helps whether you initiate or defend. Pack Tactics sets performerId=0 but still fires `EventPressureOccuring` at LOW after CardMoved — MotM would have queued fine once the playerId gate was gone.

## Fix
- Drop the pressuring-player gate; keep ≥1 Academic at `$event->location`.
- Regression: opponent pressure at Academic location must offer; Forum with no Academic still does not.
- Updated create-scheme skill docs so the next agent does not reintroduce the gate.

## Not changed
Pack Tactics LOW_PRIORITY PressureOccuring timing — already correct relative to REACTION (6) then TRANSITION (9).
