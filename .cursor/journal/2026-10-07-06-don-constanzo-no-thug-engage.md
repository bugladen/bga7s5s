# Don Constanzo City Action: Thugs never engage

## User correction
"Approach don cosranzo does not require thugs to engage to challenge characters"
(= Apropos Don Constanzo / Approach-deck Don — `_03003`).

## What was wrong
`Action_03003` step 2 conditionally engaged unengaged Thugs:
`if (! $performer->Engaged) { createCardEngagedEvent(...) }`

WHY that was written (May 14 journal + pattern-f trichotomy row): treated "No Engage printed" as "still engage like a basic challenge, but allow already-engaged performers." That put Don in trichotomy (b) instead of (c).

## Correct reading
Printed text: "Your Thug at this location issues a Combat challenge…" — **no Engage cost**. User confirms Thugs do **not** engage to issue the challenge. Same never-engage seat as Sanjay (`Action_03037`), Arrogant (`Action_03008`), Courageous (`_03058`).

## Fix
- Removed the conditional `createCardEngagedEvent` from `Action_03003`.
- Kept `DON_CONSTANZO_CHALLENGE_TYPE` **out** of `stIssueChallenge` auto-engage list (already was). Type still required so NORMAL isn't reused.
- Engaged Thugs stay eligible (`canChallenge` only) — unchanged, now consistent with never-engaging.
- `FakeGame` + `Action_03003_Test` — assert zero `EventCardEngaged` on target pick.

## Do not regress
- Do **not** restore conditional engage "because unengaged performers still engage for challenges." That was the wrong trichotomy row for this card.
- Skill docs still wrong: `.claude/skills/create-character/pattern-f.md` and `references.md` say Don uses conditional engage. Update those when next touching Pattern F docs — until then trust this journal + code WHY comments.

## Unrelated same-card work today
Reaction_03003 discard/hand eligibility (journal `-05-`) is separate; leave it alone.
