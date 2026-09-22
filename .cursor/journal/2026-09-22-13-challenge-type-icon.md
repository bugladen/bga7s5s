# Challenge type icon next to Challenger/Defender chips

## Ask
When challenge is issued, show Combat/Finesse/Influence icon next to the Challenger/Defender condition chips. Same size as the in-play character stat icons (`_7sfs-card-*-image`).

## Approach
Reuse existing `_7sfs-card-{combat|finesse|influence}-image` classes inside a positioned `_7sfs-challenge-stat-chip` wrapper — white circle, left of condition chips at 80px (chip at left:50px).

WHY nested icon inside white circle (not same element): enlarging the sprite element's box would crop wrong sprite sheet pixels; outer circle + inner image keeps sprite sizing intact.

## Implemented
- EventHub challengeIssued + getAllDatas expose raw challengeStat
- CSS `._7sfs-challenge-stat-chip` white circle at left:50px (left of condition chips)
- Utilities helpers: place/remove/updateChallengeStatChips (nested image in circle)
- Wired in Notifications (issue/swap/intervene/reject/cancel/duelEnd/duelStatChanged)
- Setup sets challengeStat before cards are built
- Reaction_03012 / Maneuver_02041 also send raw challengeStat on duelStatChanged

## Layout tweak (same session)
User: place challenge stat left of conditions + white circle behind. Done via nest + left:50px.
Then nudged 5px closer to conditions → left:55px.
Background → light grey (#d3d3d3) + 2px #888 border.
Influence icon: left -3px inside circle (wider sprite optical center).
Finesse icon: left -1px inside circle.
