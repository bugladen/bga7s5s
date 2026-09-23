# Path to Poluchatel (02045) — Elina sorcerer reaction never fired

## Bug
Elina (01118) performed Path to Poluchatel's Sorcerer City Action. Her City
Reaction ("After Elina performs a Sorcerer ability • Move a Renown…") never
opened.

## Root cause
`Action_02045` queued `createSorcererAbilityPlayedEvent` with only
`(playerId, sourceId=scheme, abilityId)` — **no performerId**. Default is 0.

`Reaction_01118` gates on `sourceId == elina || performerId == elina`. Source is
the scheme card, so neither matched.

Same class of bug as Matushka's Sight (2026-09-17-08) — missing/skipped
SorcererAbilityPlayed for Elina — but different footgun: there Pass skipped the
event entirely; here the event fired without the performer.

## Fix (`Action_02045`)
1. Pass `CHOSEN_PERFORMER` into every `createSorcererAbilityPlayedEvent`.
2. On pressure **success**, defer Played until deck search choose/pass finishes
   (ability text includes the search). On **failure**, keep Played at pressure
   result (ability ends there).

## WHY defer on success
Firing Played immediately after pressure success would open Elina's reaction
*before* the deck search (REACTION_PRIORITY 6 beats TRANSITION 8). Correct
"after she performs" timing is after the full ability, matching Action_01134's
optional-tail pattern.

## Not changed
- `Reaction_01118` itself — still correct.
- Start event already had performerId.
