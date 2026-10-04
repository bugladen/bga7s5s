# CAD Valeri + Sabre — GS1 `05DabneyUS01_2` at state 4530

## Bug
05Dabney equipped with 04054 (Sabre). Challenge Action → pick Technique_04054a
(+1 Thrust). GS1: transition `05DabneyUS01_2` impossible at state 4530
(`HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_EVENTS`).

Sabre itself is innocent — PlusOneThrust / 04054b do not queue that transition.
Only `Action_05DabneyUS01` queues `"05DabneyUS01_2"`.

## Root cause
Oct 2 Vittoria fix moved move+`"05DabneyUS01_2"` into `handleEvent` on
`EventCharacterTargeted` (Come Hither shape) so targeting reactions fire before
technique. That emit had **no `batchId`**.

Cancel/hold reactions (Vittoria, UL, Maryam) set `canceled=true`, hold a clone,
and on Decline/Pass **re-queue** the targeting event. Priority order:

1. move (MEDIUM=3) — may already have run
2. reaction offer (REACTION=6)
3. `"05DabneyUS01_2"` (TRANSITION=9) — still queued

On re-release, Dabney's handler emits a **second** move+transition. First
`"05DabneyUS01_2"` takes the game to TECHNIQUE_AVAILABLE; the leftover sits in
the DB queue. Technique activate → 4530 `stRunEvents` → leftover transition → GS1.

UL/Maryam already `deleteEventBatch` on cancel — useless without a batchId.
Vittoria never called `deleteEventBatch` at all.

## Fix
1. `Action_05DabneyUS01` — Amour/Giacinto shape: stamp `batchId` on
   `EventCharacterTargeted` at pick time; reuse on move + `"05DabneyUS01_2"`.
2. `Reaction_01014` — `cancelHeldEvent()` = `canceled=true` + `deleteEventBatch`
   when batchId set. All hold/cancel sites in handleEvent. WHY systemic: any
   batched ability Vittoria intercepts had the same orphan-on-Decline hole.

## Test
1. Valeri CAD Action targets Vittoria with Thug in hand → Decline → pick Sabre
   +1 Thrust Technique → no GS1; challenge continues.
2. Same with UL Pass on the targeting event.
3. Vittoria redirect to Thug → move goes to Thug's location; one technique pick.
4. No canceling reaction → still works (single emit).

## Follow-up (still GS1, Vittoria not in play)
User reproduced without Vittoria. Same leftover shape — any CharacterTargeted
cancel/hold/re-queue does it. Defending Honor `Reaction_02016` canceled without
`deleteEventBatch` (same hole Vittoria had).

Additional fix:
1. `Action_05DabneyUS01` — before re-emitting: `deleteEventBatch` +
   `deleteQueuedTransitionsNamed("05DabneyUS01_2")` (covers orphans lacking batchId).
2. `Reaction_02016` — `cancelHeldEvent()` with deleteEventBatch (mirror 01014).
3. `Theah`/`DB` — `deleteQueuedTransitionsNamed` helper.

## Follow-up 2 (still GS1 at ACTIVATE_TECHNIQUE)
User on technique picker after Dabney Action; activate Sabre → GS1 at 4530.
Orphan `05DabneyUS01_2` sits in `events` the whole time the picker is open;
activate → stIssueChallenge → 4530 stRunEvents → leftover transition.

Hard fix:
1. `stIssueChallenge` — purge `05DabneyUS01_2` before queueing ChallengeIssued
   (chokepoint for activate + pass + no-technique).
2. Dabney — queue `"05DabneyUS01_2"` from `EventCardMoved` (after move lands),
   not from `EventCharacterTargeted` (stops stacking hub-exit beside re-target).
3. `deleteQueuedTransitionsNamed` LIKE uses `s:N:"name"` (same as reaction query).

Must upload: Action_05DabneyUS01, StatesTrait, DB, Theah, Reaction_02016, Reaction_01014.

## Technique picker Back removed (VALERI_CHALLENGE_TYPE)
Back from ACTIVATE_TECHNIQUE → CHOOSE_TARGET after Valeri already moved: adjacent
target UI / stuck. Hide `'<'` in JS (with Servo/Andriana/Wilhelm) + server
reject in `actBack`. Not the resolve-technique Back that was reverted earlier.
