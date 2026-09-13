# Relentless Reaction_02060b: adversary same-location at resolve

## Ask
Do not apply the adversary wound when Relentless resolves if the adversary is no longer at the same location as your participant.

## Why
Card text: wound your participant • wound your opposing adversary. "Opposing" in this codebase means same location (see Reaction_04004, 03cd10, 03cd18). Offer still fires on EventDuelEnd (participants co-located then); between offer and EventRiskReactionTriggered (pay/confirm), the adversary can leave. Hubris self-wound still applies; adversary wound is gated at resolve.

## Change
In `Reaction_02060b` on `EventRiskReactionTriggered`: always queue participant wound; only queue opponent wound + both-wound notify when `$opponent->Location === $myParticipant->Location`. Else notify that adversary left.

## Not changed
- Locker/discard gate at EventDuelEnd (2026-09-05-04) — still prevents offering when either is already destroyed.
- Reaction_02060a (challenge refused) — refuse happens on-location; no analogous leave window called out.
