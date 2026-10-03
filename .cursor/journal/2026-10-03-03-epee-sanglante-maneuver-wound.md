# Action_01071 missed Maneuver_01135 as first wound

## Bug

Player A used Maneuver_01135 ("wound the adversary") in an Épée Sanglante duel. End of round, leftover threat wounded Player A. Action_01071 credited Player B as first to wound.

## Why

2026-09-29-06 tightened the handler so `sourceId` must resolve to a Character with Challenger/Defender, then adversary check. Leftover-threat EOR wounds use character sourceIds — they still work.

Maneuver_01135 (and nearly every other wounding maneuver) does:
`createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, ...)` where `$owner` is the Risk card. So `getCharacterById(sourceId)` is null → wound ignored → flag not set → later threat wound steals.

Prior journal said "non-character sources" must not steal. That was too broad: card-sourced wounds *from a participant's combat card* are still the participant wounding their adversary. True outsiders (no duel participant matching the card's ControllerId) should still be ignored.

## Fix

In Action_01071 only, when sourceId isn't a Character:
1. If abilityId is a Technique or Maneuver → `getOwningCharacter` (attachment techniques land on the equipped participant; character techniques already pass character sourceId).
2. Else map source card ControllerId → current challenger/defender (Risk maneuvers have no owning Character).

Then same participant + adversary gates as before.

WHY not change Maneuver_01135 / all maneuvers to pass actor Id: widespread convention is risk/attachment as sourceId (inject code, abilityId). Fixing attribution at the consumer that cares about "participant" is narrower and matches card text.

User follow-up: techniques must not be gated either — abilityId path makes that explicit; ControllerId fallback still covers Risk maneuvers.

## Unfinished

None for this bug. Same class of miss would have hit any maneuver/technique wound with card sourceId before leftover threat — worth remembering if similar "first to wound" effects appear.
