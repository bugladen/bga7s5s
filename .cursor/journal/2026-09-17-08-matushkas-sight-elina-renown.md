# Matushka's Sight (01134) — Elina renown reaction never fired

## Bug
Elina (01118) performed Matushka's Sight (01134). No opportunity to move Renown
(her City Reaction: "After Elina performs a Sorcerer ability").

## Root cause
`Action_01134` only queued `createSorcererAbilityPlayedEvent` on the **Engage**
path (`actFromActionWithId` with id=1). Declining the optional engage goes through
`actFromActionPass` for state `_4`, which previously only did `nextState("pass")`.

Worse: when the performer is **already engaged**, the UI hides Engage and only
shows Pass — so that completion path *always* skipped SorcererAbilityPlayed.
Elina's `Reaction_01118` listens on that event (`performerId == elina`), so the
window never opened.

Ability body (look/discard/reorder) already happened before `_4`; engage-to-draw
is optional. Played must fire either way.

Also: on Engage, `ActionResolved` was inside the engage-only branch; Pass skipped
it entirely. Now both events fire on both paths.

## Fix (`Action_01134`)
1. Engage path: always queue ActionResolved + SorcererAbilityPlayed after optional
   engage/draw (owner fetched before the id==1 branch).
2. Pass on `_4`: same two events, then `nextState("pass")`.
3. Pass on `_2` (skip discards): unchanged — ability not finished yet.

## Not changed
- `Reaction_01118` itself (still correct; city-gate from 2026-09-02-02).
- Zombie on `_4` still bare `nextState("pass")` — same incompleteness as before;
  not the live-play report.

## Feeling
Classic optional-final-step footgun. Pass looked like "I'm done" to the author
but only meant "skip engage," and the already-engaged UI made Pass the only exit.
