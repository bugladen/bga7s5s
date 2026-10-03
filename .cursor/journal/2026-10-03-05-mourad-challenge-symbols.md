# Mourad challenge symbols after Stiletto kills challenger

## Bug
Mourad intervened (Defender + challenge-stat chips). Stiletto wounded/destroyed the challenger during the challenge step. After the sequence, Mourad still had Defender + challenge-stat.

## Why (not the reaction “skipping” cleanup)
Reaction_02003 correctly swaps Defender onto Mourad. Destroy recreates the challenger (conditions wiped) and `characterDestroyed` removes their DOM. The **survivor** is never cleared on that path: no `challengeCancelled`, and Accept+dead-challenger still runs the short duel — but chips were already sticky if anything skipped/messed duelEnd. Fizzle (dead challenged) also skipped marker clear.

## Fix
1. `clearChallengeParticipantMarkers($challengerId, $defenderId)` — strip both conditions + `challengeMarkersCleared` notif.
2. `EventCharacterDestroyed`: if destroyed had Challenger/Defender and `!IN_DUEL`, clear both CHOSEN_PERFORMER/TARGET markers. WHY `!IN_DUEL`: mid-duel deaths still use duelEnd; challenge-step only. WHY not set CHALLENGE_CANCELLED: journal 2026-09-17 short-duel ruling for dead challenger stays.
3. Resolution fizzle (dead challenged) also calls the helper — same leftover class.
4. EventDuelEnd null-safe so a missing corpse never skips survivor clear / duelEnd notif.
5. JS `clearChallengeParticipantChips` shared by reject/cancel/duelEnd/challengeMarkersCleared; guards null `divId` after destroy.

## Not changed
Dead-challenger → short duel still starts (threat from last-known). Markers just do not linger on the survivor after the destroy.

## Actual sequence (user correction)
1. Challenge issued
2. Stiletto lethal on **challenger** → locker; our marker clear runs (good)
3. Mourad reaction still offered → Pass
4. Enter `highDramaChallengeActionAcceptChallenge` → JS crash

## Follow-up JS crash
`cardProperties[performerId]` still exists after destroy with `divId=null`. Enter-state did `$(null_image)` → `clearCardAsSelectable(null)` → dojo.removeClass reads `.className` on null.

Fix: null-guard `clearCardAsSelectable` / `makeCardSelectable`; Accept enter-state only highlights when `card?.divId` and image exist.

## Test
1. Stiletto lethal challenger → markers clear → Mourad Pass → Accept UI shows without JS error (dead challenger no chosen highlight).
2. Stiletto kills challenged, Accept → fizzle; living challenger loses Challenger chips.
3. Normal duel end / refuse / cancel still clear chips.
