# Sworn Swords (_03030) Implementation

## Context inherited
Continued from Hour of Blood (_03029) work in same faf scheme batch. _03030 was an empty scaffold with Text only.

## Card breakdown
1. **Resolve:** Add Renown to two different locations → `_03006` / `_03017` pattern (`actCityLocationsForReknownSelected`, `numberOfCityLocationsSelectable = 2`).
2. **Diplomat City Action:** Engage Diplomat performer → pick Duelist at that location → Combat challenge opposing character. Only Duelists intervene. +1 threat to actor on accept.

## Design choices

**Two-step HD action (03030 → 03030_2), mirroring Action_03003 (Thug issues challenge):**
Card separates performer (Diplomat, engaged) from challenger (Duelist). Can't use single performer-picker + standard challenge target flow.

**CHOSEN_CARD holds Diplomat id after engage; CHOSEN_PERFORMER becomes Duelist for challenge:**
Same split-performer pattern as 03003 storing Thug as performer. Diplomat id must survive the Duelist pick.

**New `SWORN_SWORDS_CHALLENGE_TYPE = 21`:**
Intervention gate in `Theah::interventionCheck`, `ArgumentsTrait` intervene args, and `Reaction_02058` adjacent-intervene filter — same trio as `LEGENDARY_REPUTATION_CHALLENGE_TYPE` / AJA.

**Threat via `EventGenerateChallengeThreat`:**
"If accepted, add a threat to your participant" = +1 `actorThreat` only when threat is generated (accept/intervene path), not on refuse. Matches 02061 pattern but single-sided.

**Diplomat performers filtered `!Engaged`:**
Card says "Engage your performer" — implies paying engage cost now.

**Duelists eligible while engaged if `canChallenge`:**
Same as 03003 Thug rationale — card doesn't say "unengaged Duelist".

**Did NOT engage Duelist on issue:**
Only Diplomat engages per text. Custom challenge type excluded from `stIssueChallenge` auto-engage list (like DON_CONSTANZO).

## Files
- `_03030.php`, `actions/Action_03030.php`
- `State_planningPhaseResolveSchemes03030.php`, `State_highDramaPhase03030{,_2}.php`
- `States.php`, `states.inc.php`, `Game.php`
- `Theah.php`, `ArgumentsTrait.php`, `Reaction_02058.php`
- `OnEnteringState/OnUpdateActionButtons/OnLeavingState.faf.js`, `PlayerActions.js`

## Test notes
- Planning: pick two distinct locations for Renown
- HD: Diplomat performer → Duelist pick → target → accept/refuse/intervene (non-Duelist blocked)
- Threat bonus visible on accept path for Duelist participant

## Skill update (same session)
Fed into create-scheme: Pattern E (split performer/challenger), getPerformersForAction full-legality gate, CHALLENGE_TYPE intervention trio + EventGenerateChallengeThreat accept bonus, _03030 walkthrough, Windows `\r\r\n` line-ending pitfall.
