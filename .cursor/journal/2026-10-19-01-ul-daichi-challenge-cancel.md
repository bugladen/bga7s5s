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

## Later gaps (same day)

Shared picker pay is not the only hole.

Defending Honor queued "01078" beside EventCharacterTargeted with no shared batch. UL paid on the performer, deleteEventBatch had nothing to strip, and the picker still opened. The ally pick still does not emit CharacterTargeted (the ability target is the enemy performer). Fix: batch the targeting event, and emit "01078" only when that event survives. Pass / Vittoria redirect re-deliver it and rebuild the picker for the (new) performer. A paid cancel does not re-deliver. That pay is outside the challenge machine, so UL also queues ActionResolved for the Risk — Silver Spine already did, and a batch-only cancel had stopped doing it once the event gained a batchId. Spine now deletes the batch and still resolves.

Hub-exit challenges (Move Along, Servo, Valeri Mikhailov, Aja, Don Constanzo, Sworn Swords, Sanjay, Danilo, Raven, Wilhelm, Torvo, Rattle the Rigging) and Defending Honor's ally never get that target-pick window. Issue-time already canceled when the stamped ability was still the action. Activating a technique overwrites TRANSITION_INTERNAL_ID, so abilityId was the technique and UL stayed silent. CHOSEN_ACTION is still the issuing action (technique activation does not clear it — Move Along's check-cancelled comment already relies on that). ChallengeIssued now falls back to CHOSEN_ACTION when the stamp is not a targeting ability. Paying still sets CHALLENGE_CANCELLED via the held ChallengeIssued, which check-cancelled already honors.

Did not treat every technique id as targeting. A CHOSEN_ACTION that is not a targeting ability still does not offer. Andriana / CAD Valeri were already gated on the pull or the targeting event and were left alone.
