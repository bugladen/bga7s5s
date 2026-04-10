# Daniella Dietrich (01036) Audit

## Card Text
- **City Action:** Your Mercenary at this location issues a [Combat] challenge to target opposing character.
- **Technique:** When your round ends, move Daniella to an adjacent City location. Usable once per Day.

## City Action — Mercenary Issues Challenge
The action flow is solid: RequiresPerformerSelected=true, getPerformersForAction returns Mercenaries at Daniella's location, isValidTargetForAbility validates opposing + same location, handleEvent sets CHALLENGE_TYPE and CHALLENGE_STAT then transitions to challenge setup. The challenge setup (stSetupChallenge) handles setUsed, announceAction, and resetPlayerPassCount for DANIELA_DEITRICH_CHALLENGE_TYPE.

## Technique — Move to Adjacent Location
Uses deferred execution: player picks a location during technique phase (MoveDaniela flag + MoveLocation), then the actual move fires on EventDuelEndOfRound. ResetOnDuelEnd=false, ResetOnDayEnd=true correctly implements "Usable once per Day". Adjacent locations exclude Home ($includeHome=false). Server-side validates chosen location is actually adjacent.

## Bugs Found & Fixed

### 1. Missing engagement check for Mercenary performers (Action_01036)
Both `isAvailableToPlayer` and `getPerformersForAction` filtered Mercenaries using only `canChallenge()` (which just checks `isControlled()`). The normal challenge framework checks `canChallenge() || Engaged`, with a special case for Carmella (_01178) who can challenge while engaged. An engaged Mercenary could issue a challenge through Daniella, violating standard challenge rules.

**Fix**: Added engagement check matching the framework pattern: `instanceof _01178` uses only `canChallenge()`, everyone else checks `canChallenge() && !Engaged`.

### 2. Missing EventDuelEnd cleanup (Technique_01036)
If the duel ends before EventDuelEndOfRound fires (e.g. a character destroyed mid-round), MoveDaniela stays true. In a future duel within the same day, a stale EventDuelEndOfRound could trigger a move to the previously-chosen location. Technique_01096 handles EventDuelEnd to clean up its IsActive flag — Technique_01036 was missing this.

**Fix**: Added EventDuelEnd handler that resets MoveDaniela and MoveLocation when the duel ends with the flag still set.

## WHY: The Carmella special case
Carmella (_01178) has the Mercenary trait and can "issue a challenge or intervene in one even while engaged" once per day. Her `canChallenge()` override returns true when engaged (if ability unused). The framework's engagement check is separate from `canChallenge()` — the base class only checks isControlled(), and callers add `|| Engaged` separately. This means any code that filters for challenge-capable characters needs the `instanceof _01178` carve-out, or it would incorrectly exclude Carmella while engaged. This is a codebase-wide pattern found in ArgumentsTrait, Theah.php, and FrameworkActionsTrait.

## WHY: No actorId check in EventDuelEndOfRound handler
Unlike Technique_01063/01096 which check `$event->actorId` to determine whose round ended, Technique_01036 doesn't need this check. The technique uses a flag-based approach: MoveDaniela is set during the technique phase and consumed by the very next EventDuelEndOfRound. Since rounds are sequential, the next EventDuelEndOfRound is always the end of the round in which the technique was activated.

## Items verified as correct
- Challenge stat set to STAT_COMBAT ✓
- Mercenary (performer) is the challenger, not Daniella ✓
- Target must be opposing and at performer's (= Daniella's) location ✓
- Technique only available during duel (isAvailableToPlayer checks IN_DUEL) ✓
- Adjacent locations exclude Home ✓
- Server-side location adjacency validation in actFromTechniqueWithIds ✓
- EventTechniqueCanceled resets MoveDaniela ✓

## Files Changed
- `modules/php/cards/_7s5s/actions/Action_01036.php` — bug 1
- `modules/php/cards/_7s5s/techniques/Technique_01036.php` — bug 2
