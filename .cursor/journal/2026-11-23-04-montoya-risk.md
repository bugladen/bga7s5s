# _05Montoya The Aldana Ruse

Finishing CAD Risk via create-risk. User: no clienttranslate / translate / _().

## Orientation
Prior today: Shakedown B.2 RiskAttachment + CharacterTargeted. Recent CAD maneuvers: Stash C.11, Glass A.17+wound.

## Text → shapes
1. **Pattern E** "While you are not first player, this card has -1 Cost." Maneuver-only Risk → `getManeuverFromCombatCardDiscount` on Maneuver. Gate `FIRST_PLAYER != owner` + Lorenzo `OVERRIDE_AS_NOT_FIRST_PLAYER` (01112b discipline).
2. **Duelist Maneuver** +1 Riposte per Flourish in dueling line + draw then put a hand card on top of deck.
   - Calc: count Flourish at `LOCATION_DUELING_LINE` for controller — **include self** (print says "each Flourish", not "other"; this card is Flourish and already in line). Contrast C.4 `unset($cards[$owner->Id])`.
   - Resolve: queue draw, then transition to hand chooser if hand nonempty OR faction deck nonempty (draw will fill). Skip chooser when both empty to avoid softlock.
   - Chooser: RedTape/03006 hand→deck sync path with `onTop=true` + `cardRemovedFromHand` (EventCardAddedToFactionDeck alone misses hand UI).

## WHY
- No Cesca — hand chooser, print never says "target".
- Castille → only `DUEL_RESOLVE_MANEUVER_EVENTS` (not Miyato Neutral/Ussura mirror).
- State id `52505029` (exp5 + CardNumber 29).
- Include self in Flourish count — print is not C.4 "other".
- Hand→deck: RedTape sync + cardRemovedFromHand (EventCardAddedToFactionDeck alone leaves Location/hand UI wrong).

## Shipped
- `_05Montoya` + `Maneuver_05Montoya` + `State_duelResolveManeuver_05Montoya`
- Wire `"05Montoya"` under DUEL_RESOLVE_MANEUVER_EVENTS; cad JS trio + EventHandlers
- Skill references + pattern-c C.4 sibling note
- Tests: `Maneuver_05Montoya_Test` (3) — all pass

## Unfinished / watch
- Did not add Cesca allow-list (CAD prototypes skip until asked).
- Zombie on hand chooser just nextState() — no auto-pick (same as Pocketknife).
