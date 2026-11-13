# Unyielding Loyalty cancel left basic-challenge challenger en garde

## Bug
Basic Challenge issued → Unyielding Loyalty paid at target-pick → challenge cancelled correctly, but the challenger stayed standing (not Engaged).

## Why
Engage for NORMAL / Servo / Aja / Danilo / Raven lives only in `stIssueChallenge`. UL (and other cancels) set `CHALLENGE_CANCELLED` on the CharacterTargeted window; `stTechniqueAvailable` then calls `skipChallengeBeforeTechnique` and never reaches issue. Cost was never paid; cancel does not refund costs.

Same class of "pay cost before cancel can strip the effect" as Hulk Rage engage-at-announce — here the cost was deferred to issue for historical reasons, so the pre-issue cancel path had to pay it.

## Fix
`skipChallengeBeforeTechnique` queues `EventCardEngaged` for types on the shared `challengeTypeEngagesChallengerOnIssue` list when the challenger is still en garde, before ActionResolved. Extracted that list from `stIssueChallenge` so the two paths cannot drift.

Did **not** move engage earlier to target-chosen: that would fire engage reactions before technique pick / issue log and change happy-path timing. Cancel-path pay is enough.

Flee / Diplomatic Envoy skips in `stTechniqueAvailable` still use their own ActionResolved path (not `skipChallengeBeforeTechnique`) — those do not newly engage Servo, which matches "challenge could not legally be issued" rather than "ability cancelled after announce."

## Tests
Extended `StatesTrait_Challenge_Test`: NORMAL cancel queues engage; Torvo cancel does not; already-engaged challenger is not re-engaged.
