# Action_01071 available with no opposing character

## Report
Épée Sanglante City Action lit when there was a Musketeer in the city but no opposing character to challenge.

## Root cause
`isAvailableToPlayer` / `getPerformersForAction` only gated on Musketeer + `canChallenge`. `canChallenge` is just "is controlled" — no target check. Card text requires "target opposing character".

Siblings already do this right: `Action_04045` / `Action_02061` / `Action_03030` require `getOpposingCharactersAtLocation(...) > 0` on the performer.

## Fix
- `getPerformersForAction`: Musketeer + canChallenge + co-located opposing character.
- `isAvailableToPlayer`: `count(getPerformersForAction) > 0` (same pattern as 04045).
- Regression: availability needs a local foe; new case for Musketeer + far foe only; performers exclude lonely Musketeers.

## WHY not only in isAvailable
Also filter performers — otherwise you could pick a Musketeer at an empty location while a different Musketeer has a foe, then softlock on empty choose-target.

## Tests
`php tests/run.php tests\regression\core\Action_01071_Test.php` — 19/19 PASS.
