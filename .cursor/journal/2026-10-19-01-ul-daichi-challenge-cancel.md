# Unyielding Loyalty paid, Daichi's challenge still issued

## Bug
Reaction_01032 triggered against a challenge from Mōri Daichi (03050). Wealth plus Red Hand/Thug were paid. The challenge still resolved.

## Why
Framework target pick queues only EventCharacterTargeted (abilityId BasicChallenge, or the scheme action). UL intercepts that, player pays, clearEvents drops the held event. It set CHALLENGE_CANCELLED only when the held event was EventChallengeIssued.

The duel is not a batched sibling. endOfEvents on target-chosen always enters stTechniqueAvailable, which issues unless that flag is set. Daichi has a technique, so the picker opened and the challenge went out. stIssueChallenge then writes CHALLENGE_CANCELLED false, so a flag set too early would be wiped anyway — it has to be set on pay, and the technique gate has to see it before issue.

## Fix
On successful additional-cost pay, if the held event is EventCharacterTargeted, the reaction state is HIGH_DRAMA_CHALLENGE_ACTION_TARGET_CHOSEN_REACTIONS (4511), and abilityId matches CHOSEN_ACTION, set CHALLENGE_CANCELLED. stTechniqueAvailable already calls skipChallengeBeforeTechnique.

## Why the state id
CHOSEN_ACTION match alone is true for Press the Advantage and every other targeting action. CHALLENGE_TYPE is reset to NORMAL at each High Drama turn, so it cannot tell "basic challenge in progress" from "not challenging." A stale true flag skips the next basic challenge at stTechniqueAvailable. 4511 is the only window before the duel is issued.

Pass and "no Red Hand/Thug left" still releaseEvent and do not set the flag.

## Left alone
If the player passes UL at target pick and Daichi then activates Technique_03050, EventChallengeIssued's abilityId is the technique (actHighDramaChallengeActionTechniqueActivated overwrites TRANSITION_INTERNAL_ID). shouldReactToEvent misses that. Target-pick is the printed window and now actually stops the duel when paid. Do not "fix" issue-time by treating every technique id as a targeting ability.
