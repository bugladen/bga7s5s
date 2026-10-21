# Unyielding Loyalty offered before the challenge is in the log

## Bug
After Cesca moved to the Forums, the opponent started a basic challenge. Unyielding Loyalty opened on EventCharacterTargeted (target-chosen reactions, state 4511) with no log line and no duel markers. The player passed and only then saw it was a Combat challenge.

## Why the log was empty
`${player_name} has chosen ${challenger} to Challenge ${defender}` plus the Combat/Finesse/Influence sentence is written in the EventChallengeIssued hub handler. That event is queued from stIssueChallenge, which is after the technique window. UL holds the targeting event before any of that. Duel markers come from the `challengeIssued` notification type, so they are absent on purpose until the challenge actually exists.

Hub-exit challenges that only emit ChallengeIssued already log first: runEvents runs the hub before card reactions. This hole is only the framework target pick.

## Fix
`actHighDramaChallengeActionTargetChosen` calls `announcePendingChallenge` after the target is accepted and before `nextState("targetChosen")`. Same two sentences as the issue log. Notification type is `message`, not `challengeIssued`.

WHY not `challengeIssued`: that type paints challenger/defender markers. Painting them before UL can still cancel would put a duel on the board that does not exist yet. The user asked for the log line and the stat, minimum.

WHY no technique sentence here: the technique is chosen after this reaction window. "No Technique will be activated" would be a lie if they then activate one. That sentence stays on the real issue notification.

WHY the later copy stays: if they pass, stIssueChallenge still says "is issuing a challenge" and ChallengeIssued repeats the chosen line plus the technique. Duplicate on purpose. Stripping the later copy would drop the technique sentence or force a second wording path for hub-exit issues that never came through this picker.

A refused target (invalid / unknown) returns before the announce.

## Tests
FrameworkActions_Challenge_Test: basic target pick logs chosen + Combat and type `message`; invalid target logs nothing; Finesse stat is in the line; Defending Honor (no CharacterTargeted) still logs. Full runner 5492 passed.
