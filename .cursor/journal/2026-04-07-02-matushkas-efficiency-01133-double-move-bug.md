# Matushka's Efficiency (01133) Double Move Bug

## The Bug
When 01133's Sorcerer Action moved a character, Stubborn (01140) triggered twice. Also, canceling the movement with Stubborn didn't actually prevent the move.

## Root Cause
`Action_01133::actFromActionWithIds` (state `01133_2`) created and queued a `CardMovingEvent` at the point when the player chose the destination location. Then `Action_01133::stateFromAction` (state `01133_3`) created and queued a second identical `CardMovingEvent`. Two separate move events → Stubborn fires on each one. Canceling the first doesn't stop the second.

## Fix
Removed the premature move event from `actFromActionWithIds`. The move only needs to happen once, in the `01133_3` game auto-state where `actionResolvedEvent` and `sorcererAbilityPlayedEvent` are also queued. The `actFromActionWithIds` method's job is to record the player's choice and set up the transition — not to execute the move.

## WHY only in stateFromAction?
The `01133_2` → `01133_3` transition exists because the engage-for-discount choice (Reaction_01133) and sorcererAbilityStartedEvent need to process before the actual move. The move belongs in `01133_3` because that's the game state that resolves everything.
