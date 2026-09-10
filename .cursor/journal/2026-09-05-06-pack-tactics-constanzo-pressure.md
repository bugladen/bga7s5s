# Pack Tactics + Constanzo pressure bonus missing

## Bug
Eddie: Pack Tactics (01028) pressured a location after moving a thug there. Don Constanzo (01006) did not grant his +1.

## Root cause
`Action_01028` queues: CardMoving(s) → EventPressureOccuring → Transition(pressureLocation).

EventHub turns each CardMoving into a trailing EventCardMoved (same MEDIUM priority, later event_id). Queue order by `(priority, event_id)`:

1. CardMoving (MEDIUM) → appends CardMoved
2. **PressureOccuring (MEDIUM)** ← Constanzo `handleEvent` looks for Thugs at location — still adjacent
3. CardMoved (MEDIUM) ← thugs actually arrive
4. Transition (prio 8) → `pressureLocation()` — thug Influence counted; CONSTANZO flag never set

Transition already waited correctly (prio 8). Only PressureOccuring was early. Pack Tactics' own PRESSURE_BONUS is set synchronously so that still applied — user only saw Constanzo missing.

## Fix
Set `$pressureOccuringEvent->priority = Event::LOWEST_PRIORITY` in `Action_01028` (same pattern as `EventActionResolved`). LOWEST (5) runs after MEDIUM CardMoveds; Transition still after that.

WHY not peek queued destinations in `_01006`: would paper over one consumer; any EventPressureOccuring listener that reads board state would stay wrong for Pack Tactics. Fix at the source so "pressure occurring" means moves have landed.

## Cancel / Stubborn
If a move is canceled, no CardMoved for that thug; PressureOccuring at LOWEST still runs (correct — thug not at location). Decline re-queues CardMoving at MEDIUM, which still sorts before LOWEST PressureOccuring.
