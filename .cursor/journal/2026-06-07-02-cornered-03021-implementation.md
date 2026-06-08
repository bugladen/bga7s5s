# Cornered (Risk _03021) Implementation

## Card Details
- **Cornered**: Eisen Risk (Riposte 0, Parry 2, Thrust 3) / WealthCost 0
- Traits: Challenge, Hunt, Zeal
- Text: **City Action:** Engage your performer • They issue a [Combat] challenge to target opposing **Sorcerer** or **Monster**. If they refuse, engage them. Wound any character that intervenes.

## Mechanics Breakdown
1. **City Action with automatic performer engagement** → RiskCityAction in Action_03021
2. **Trait-filtered target (Sorcerer OR Monster)** → IAbilityThatTargetsCharacters with dual-trait filter
3. **If target refuses, engage them** → EventChallengeRejected handler on the Risk class
4. **Wound any intervener** → EventCharacterIntervened handler on the Risk class
5. **Mark IRiskThatTargetsCharacters on Risk class** itself

## Implementation Plan
1. Action_03021 extends RiskCityAction implements IAbilityThatTargetsCharacters
   - RequiresPerformerSelected = true
   - Engage performer on trigger
   - Filter targets to opposing Sorcerer OR Monster at performer's location
   - Use shared HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET state
   
2. Risk class (_03021)
   - implements IHasActions, IRiskThatTargetsCharacters
   - EventChallengeRejected handler: engage target if not already engaged
   - EventCharacterIntervened handler: wound the intervener

## Key Design Decisions
- **NORMAL_CHALLENGE_TYPE** since no special intervention rules (wound intervener is a consequence, not a rule change)
- Performer engagement happens in Action's handleEvent before challenge is issued
- Target engagement on refuse is optional (only if not already engaged - check prevents double-engaging)
- Intervener wound is unconditional

## Notes on Similar Cards
- _01083 (Legendary Reputation): Same challenge-issuing pattern but with custom challenge type
- _01119 (Nazem ibn Umur): Refusal handler that engages target (pattern to mirror)

## Implementation Complete

### Files Created
1. `/modules/php/cards/faf/actions/Action_03021.php` - RiskCityAction with Sorcerer/Monster trait filtering
2. Updated `/modules/php/cards/faf/_03021.php` - Added Action + EventChallengeRejected + EventCharacterIntervened handlers

### Files Modified
1. `/states.inc.php` - Added "03021" transition to HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET

### Key Implementation Details
- Action engages performer in handleEvent before the challenge is issued via queueEvent chain
- EventChallengeRejected handler engages the target only if not already engaged (prevents double-engaging)
- EventCharacterIntervened handler unconditionally wounds interveners with eventCheck + queueEvent

## Post-skill Fix (same session)

The skill-generated handler gated on `$event->actionId == $this->Id`, but **neither `EventChallengeRejected` (fields: `challengerId`, `targetId`) nor `EventCharacterIntervened` (fields: `playerId`, `oldTargetId`, `newTargetId`) has an `actionId` field**. That gate never fired — the engage-on-refuse and wound-on-intervene effects were silently dead.

WHY a fresh CHALLENGE_TYPE is the right correlator (and not e.g. `challengerId == performerId`):
- The Risk is the source of the action, not the challenger — the challenger varies per-play (chosen performer). The Risk class can't pin "is this my challenge?" off the challenger id alone (a future card could legitimately have the same performer challenge separately).
- `CHALLENGE_TYPE` is already the codebase's "this challenge has special rules" channel; the framework reads it in `Theah::interventionCheck`. Adding `CORNERED_CHALLENGE_TYPE = 20` and gating both handlers on `globals->get(CHALLENGE_TYPE) == CORNERED_CHALLENGE_TYPE` is the same pattern `_01083` Legendary Reputation, `_03008` Arrogant, etc. use to disambiguate their challenges from baseline ones — even when their *intervention/refusal gates* are normal (as here, where Cornered allows refusal and intervention freely, then attaches side effects).
- Note the create-risk SKILL says "add a new CHALLENGE_TYPE constant **only** when intervention or refusal rules differ from normal." That guidance is too narrow — it conflates "differ" with "block." Cornered's intervention/refusal *gates* are normal (anyone can do either), but the *consequences* differ, and the Risk handler still needs a correlator to fire only on its own challenges. Side-effect-on-intervene/refuse is a legitimate reason to mint a CHALLENGE_TYPE even when the gates are baseline.
- All traits exist in TraitNames.php
- Pre-commit hook requirements satisfied:
  - ✓ RiskCityAction has createActionResolvedEvent comment
  - ✓ IRiskThatTargetsCharacters marked on Risk class
  - ✓ No Maneuver/Reaction in this card
  - ✓ Not mixing trait-targeting interfaces
- PHP linting: no syntax errors
