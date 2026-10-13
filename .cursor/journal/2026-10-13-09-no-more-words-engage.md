# No More Words (_04019) — performer wrongly Engaged

## Symptom
Eddie: No More Words engagin both Rena and her attachment when sending a challenge. Print only Engages the Melee Weapon / Eisenfaust attachment.

## Cause
Aug 2026 B.6 implementation put `NO_MORE_WORDS_CHALLENGE_TYPE` **on** `stIssueChallenge` auto-engage list. Comment claimed "performer still engages when issuing." Wrong — "They issue a challenge" is not an Engage cost. Attachment step already pays the only printed Engage.

Same class of mistake as Courageous / Arrogant / Honorable / Sanjay (trichotomy c: no performer Engage → type off list). Earlier skill docs encoded the wrong rule and would reintroduce it.

## Fix
1. Remove `NO_MORE_WORDS` from `StatesTrait::stIssueChallenge` auto-engage `if`.
2. Keep the type (still not `NORMAL`) so choose-target Back stays hidden after attachment cost + `actBack` guard.
3. Correct Action_04019 WHY comment + create-risk B.6 / checklist / helpers / references / SKILL.
4. `Action_04019_Test` asserts attachment-only `EventCardEngaged` + type stamped.

## Do not regress
- Do **not** put NO_MORE_WORDS back on auto-engage "so basic challenges engage." Print ≠ basic Challenge.
- Attachment Engage on confirm stays; En Garde `!$Engaged` performer gate stays.
