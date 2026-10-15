# CAD Valeri + Soline — challenge after target flees

## Symptom
Action_05DabneyUS01 used vs Soline (_03040). Reaction_03040 moved Soline to another
City location after Valeri arrived. Challenge still issued / Technique picker still
opened. Wrong — target no longer co-located.

## Root cause
Move-then-challenge timing vs reaction priority:

1. Surviving `EventCharacterTargeted` queues Valeri's `EventCardMoving` (engage=false).
2. Valeri's `EventCardMoved` queues hub-exit `"05DabneyUS01_2"` at TRANSITION_PRIORITY (9).
3. Same `EventCardMoved` also lets Soline queue `reaction` at REACTION_PRIORITY (6).
4. Lower number runs first → Soline flees → then `"05DabneyUS01_2"` still enters
   TECHNIQUE_AVAILABLE / `stIssueChallenge` with stale CHOSEN_TARGET.

Checking co-location on Valeri's own `EventCardMoved` is too early (Soline still there).
Deleting `"05DabneyUS01_2"` from Soline's later move works but is fragile if the
transition was already consumed.

## Fix
Mirror Andriana `Action_02001` (journal 2026-09-01-03):

- `Action_05DabneyUS01::shouldIssueChallenge()` — CHOSEN_TARGET shares Valeri.Location.
- `stTechniqueAvailable`: for `VALERI_CHALLENGE_TYPE` (and existing Andriana type), if
  false → `createActionResolvedEvent` + `challengeSkipped` → NEXT_PLAYER.

WHY stTechniqueAvailable: by then CardMoved reaction windows have drained; location
is authoritative. Same reason Andriana didn't hook a single EventCardMoved.

## Tests
`tests/regression/Action_05DabneyUS01_Test.php` — shouldIssueChallenge co-location /
missing target; targeting→move no-engage + VALERI_CHALLENGE_TYPE; CardMoved queues
`05DabneyUS01_2`. FakeGame gained `VALERI_CHALLENGE_TYPE = 32`.

## Log note (follow-up)
User asked for a game log when skip fires. `stTechniqueAvailable` now notifies
`${performer} cannot issue a challenge to ${target} — they are no longer at the
same location.` (fallback without target inject if CHOSEN_TARGET is gone). Shared
path covers Andriana + CAD Valeri.

## Not changed
Base Valeri `Action_01123` still queues `"01123_2"` without CharacterTargeted /
shouldIssueChallenge — same class of bug if Soline flees. Left alone (CAD report).
