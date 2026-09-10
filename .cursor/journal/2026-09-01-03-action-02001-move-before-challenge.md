# Action_02001 — challenge only after successful move

## Problem

Andriana's City Action text is "Move target … Then, she issues them a [Combat] challenge." The action queued the challenge transition (`02001_2`) immediately after `createCardMovingEvent`, so if movement was cancelled (Stubborn, etc.) or otherwise failed, `stIssueChallenge` still ran.

## Fix

Added `Action_02001::shouldIssueChallenge(Game $game): bool` — compares `CHOSEN_TARGET` location to Andriana's location after move events have flushed.

`stTechniqueAvailable` calls it when `CHALLENGE_TYPE == ANDRIANA_DONDOLOS_CHALLENGE_TYPE`. If false: queue `createActionResolvedEvent`, `runEvents(true)`, `nextState('challengeSkipped')` → `NEXT_PLAYER`.

WHY hook stTechniqueAvailable instead of deferring the transition from handleEvent/EventCardMoved:
- Stubborn can stack a reaction transition *before* the canceled move event runs; a decline re-queues move *after* sorceryPlayed in the same batch. Any single-event hook (CardMoved alone, or SorcererAbilityPlayed) can fire at the wrong time relative to a re-queued move.
- By the time we enter `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`, the full event batch from character selection (including any reaction/re-queue cycles that completed first) is done, so location is authoritative.

Kept the logic on Action_02001 (not inline in StatesTrait) so Cesca copies and future callers share one definition of "did the move succeed."

## Unfinished

None. Servo (01011) has the same structural pattern (move then challenge) but wasn't in scope.
