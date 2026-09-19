# Improvising (01106) — discard to OwnerId, not ControllerId

## Ask
Eddie: When a risk is stolen by Improvising, when that stolen card is discarded it should return to the discard pile of OwnerId, not ControllerId.

## Root cause
`Action_01106` created `_01106_RiskClone` with both `OwnerId` and `ControllerId` set to the Improvising player. Framework discard-from-hand uses `$risk->OwnerId` for the pile, so the clone (visually the stolen risk via `RiskClonePropertyTrait`) landed in the thief's discard. Same OwnerId==ControllerId conflation meant EventHub hand UI and discard pile shared one playerId.

## Fix
1. **Action_01106**: `createCardInLocation(..., $riskCard->OwnerId, $owner->ControllerId)` — ownership stays with the stolen card's owner; control is the thief so pay/play still works (`actPayForInHandAction` checks ControllerId).
2. **EventHub EventCardDiscardedFromHand**: discard pile from `$card->OwnerId`; handCount/playerId from `$card->ControllerId`; notify adds `discardPlayerId`.
3. **Notifications.js**: push to `discardPlayerId` discard array; hand removal still keyed on `playerId` (controller).
4. **_01106_RiskClone**: remove clone from `$this->OwnerId` discard; sink original with `$clonedCard->OwnerId` (matches card text parenthetical).

## WHY not only change the sink
Eddie said discard pile specifically. The clone's AsPlayed discard is what players see as "the stolen card" going to a pile. Sink-only would leave that wrong for the momentary discard + any OwnerId!=ControllerId discard path.

## Not changed
01124 / 01154 clone creation still sets both ids to the casting player (they pull from *your* discard, so Owner≈Controller). Same EventHub/JS path helps if a mismatched card is ever there.
