# Unsanctioned Duel (_02061) Implementation

## Card Text
"Duelist City Action: Engage your performer - Issue an unrefusable [Combat] challenge to target opposing non-Leader. At the start of the first round of the following duel, add a threat to both participants. These effects cannot be cancelled."

## What Was Done
Implemented the full "Duelist City Action" for Unsanctioned Duel. This is a Risk card that issues a Combat challenge through the standard challenge flow.

### Files Created
- `modules/php/cards/tac/actions/Action_02061.php` - RiskCityAction implementing IAbilityThatTargetsCharacters

### Files Modified
- `modules/php/cards/tac/_02061.php` - Added IHasActions, IRiskThatTargetsCharacters, ActionTrait, Action_02061
- `modules/php/Game.php` - Added UNSANCTIONED_DUEL_CHALLENGE_TYPE = 17
- `modules/php/FrameworkActionsTrait.php` - Block rejection for this challenge type (same pattern as Epee Sanglante)
- `modules/php/StatesTrait.php` - stSetupChallenge: engage performer
- `modules/js/OnUpdateActionButtons.js` - Disable Refuse button for this challenge type
- `seventhseacityoffivesails.js` - Added JS constant for challenge type
- `states.inc.php` - Added "02061" transition mapping to HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET

## Key Decisions

### WHY: Standard challenge flow (not custom states)
Used the same pattern as 02049 (Justice Served Cold) - transitions to HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET rather than custom states. The standard challenge flow handles performer selection, target selection, accept/reject/intervene, and duel setup. No need to reinvent it.

### WHY: Engagement in stSetupChallenge, not in action's handleEvent
The engagement needs to happen AFTER target selection is confirmed and the challenge is being set up. Putting it in handleEvent would engage the performer immediately when the action triggers, before the target is even chosen. The stSetupChallenge is the right place because it runs after target selection and before the accept/reject phase.

For attachment actions (Cavalier Hat, Triskelion), stSetupChallenge already has engagement logic using getInPlayActionById + getOwningCharacter. But risk actions aren't "in play" actions, so I used the TRANSITION_SOURCE_ID global to get the risk card ID and CHOSEN_PERFORMER for the character to engage.

### WHY: Threat via EventGenerateChallengeThreat (not PENDING_THREAT)
Initially used PENDING_CHALLENGER/DEFENDER_THREAT globals but moved to handling EventGenerateChallengeThreat in Action_02061's handleEvent. This is better because the threat gets announced through the EventHub's notification system (the announcement that shows threat totals). The action checks CHALLENGE_TYPE == UNSANCTIONED_DUEL_CHALLENGE_TYPE and adds +1 to both actorThreat and adversaryThreat with an explanation message. Since EventGenerateChallengeThreat has runEventHubAfterCards = true, the action modifies the event before EventHub announces the totals.

### WHY: Unrefusable = block in actHighDramaChallengeActionReject + disable JS button
Same dual pattern as Epee Sanglante: server-side throws BgaUserException if someone tries to reject, and client-side disables the Refuse button to prevent the attempt. Belt and suspenders.

### WHY: No back button in choose-target state
Risk card challenge types (like 02049) don't show a back button on the choose-target screen. The JS only shows back buttons for explicitly listed types (Normal, Triskelion, Epee Sanglante, Cavalier Hat). This is intentional - once you've triggered a risk card challenge action, you're committed.

## Performer Requirements
- Must have "Duelist" trait
- Must not be Engaged (since we're engaging them)
- Must be in the city
- Must canChallenge()
- Must have at least one valid target (opposing non-Leader at same location)
