# Battle of Wits (02028) Implementation

## Card Text
- **Diplomat City Action:** Your performer issues a [influence] challenge to target opposing character with 1[influence] or more.
- **Maneuver:** +X[thrust] where X is equal to your participant's [influence]. If your participant is a Diplomat, gain Lethal.

## What Was Done

Implemented both abilities on the Montaigne faction Risk card Battle of Wits (`_02028`):

### Diplomat City Action (Action_02028)
Follows the same influence challenge pattern as Veronica's Guile (`Action_01033`) but with Diplomat performer restriction from Tea and Cakes (`Action_02025`).

- Performer must be a Diplomat, must be able to challenge, must not have DashedInfluence
- Target must be opposing, same location, and have `ModifiedInfluence >= 1`
- Sets `CHALLENGE_STAT = STAT_INFLUENCE` and `CHALLENGE_TYPE = BATTLE_OF_WITS_CHALLENGE_TYPE`
- Transitions to `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` (standard challenge flow)

### Maneuver (Maneuver_02028)
Two-part maneuver combining patterns from `Maneuver_01139` (+X thrust from stat) and `Maneuver_01057` (gain Lethal):

- `EventDuelCalculateManeuverValues`: Adds `$actor->ModifiedInfluence` to thrust
- `EventResolveManeuver`: If actor `hasTrait("Diplomat")`, calls `createGainLethalEvent`

## Key Design Decisions

**WHY ModifiedInfluence for thrust bonus:** Used `ModifiedInfluence` (not base `Influence`) because during a duel the effective stat value should include bonuses from attachments/events (e.g. Éventail's +1 influence while en garde). The card text says "your participant's [influence]" which in this game's convention refers to the current effective value.

**WHY ModifiedInfluence >= 1 for target filter:** The card says "target opposing character with 1[influence] or more". Using ModifiedInfluence here because a character could have their base influence but have it modified to 0 via effects. If a character effectively has 0 influence, they shouldn't be a valid target. Characters with `DashedInfluence` would already have `ModifiedInfluence = 0`.

**WHY a new challenge type constant (BATTLE_OF_WITS_CHALLENGE_TYPE = 14):** Each card that issues a challenge gets its own type constant. This is the established pattern — even Veronica's Guile (which is a straightforward influence challenge) has its own type. The challenge type affects behavior in `stIssueChallenge` (whether performer is engaged) and `stSetupChallenge` (whether setUsed/announceAction are called there). Battle of Wits follows the same non-engaging pattern as Veronica's Guile — the performer is NOT automatically engaged when issuing this challenge (since `BATTLE_OF_WITS_CHALLENGE_TYPE` is not `NORMAL_CHALLENGE_TYPE` or `SERVO_SCARPA_CHALLENGE_TYPE`).

**WHY no custom states needed:** The standard challenge flow (`HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` → technique → setup → issue → generate threat → resolution) handles everything. Target filtering is done via `isValidTargetForAbility` on the action. No card-specific states are required.

## Files Created
- `modules/php/cards/tac/actions/Action_02028.php`
- `modules/php/cards/tac/maneuvers/Maneuver_02028.php`

## Files Modified
- `modules/php/cards/tac/_02028.php` — added interfaces, traits, wired Action + Maneuver
- `modules/php/Game.php` — added `BATTLE_OF_WITS_CHALLENGE_TYPE = 14`
- `states.inc.php` — added `"02028" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` transition

## Open Questions
- Need to verify via BGA studio that the challenge flow correctly handles setUsed/announceAction/resetPlayerPassCount for this challenge type. Veronica's Guile (`VERONICAS_GUILLE_CHALLENGE_TYPE`) is not in the `$types` array in `stSetupChallenge`, so those calls don't happen there. Action_01033's comments claim they're called in stSetupChallenge but the code says otherwise. This is a pre-existing pattern question, not specific to this card.
