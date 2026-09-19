# Night of Drinking (01109) — missing ActionResolved after cancel

## Bug
Eddie: cancelled opponent's Come Hither (01162) with Night of Drinking. Soline
(in city) never got her Reaction_01089 window; game went straight into Eddie's
High Drama turn.

## Root cause
01109 cancel deletes `EventActionTriggered`. Come Hither only queues
`createActionResolvedEvent` in `actFromActionWithIds` after the move — so that
never runs. `HIGH_DRAMA_PLAYER_TURN_EVENTS` then hits `endOfEvents` →
`NEXT_PLAYER` with no Soline window.

Same gap for any Risk Action cancelled by 01109 whose ActionResolved lives on
the effect-completion path (most of them).

## Rules
"Cancel its effects. (All costs are still paid.)" — Action sequence still
completes. Soline: "After an Action resolves" — cancelled Action still resolves
(effects cancelled). Must fire ActionResolved.

## Fix
`Reaction_01109`: track `$CancelledActionPlayerId` on `EventActionActivated`
offer path only (clear on Reaction / Maneuver / Not Today offers). On cancel,
if set, queue `createActionResolvedEvent` for that player after deleting
Triggered/Played.

WHY store announcer id rather than `$risk->ControllerId`: ActionActivated's
playerId is the announcer at offer time; card may already be in Purgatory by
cancel. WHY not fire for Reaction/Maneuver cancels: those are not HD Actions;
Soline must not get a free window off canceling a Risk Reaction mid-duel etc.

## Not changed
Come Hither itself — problem is systemic on the canceler, not 01162.
