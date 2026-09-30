# Courageous _03058 — City Action never engages

Eddie correction: Courageous Duelist City Action issues a Combat challenge **without** engaging the performer, and it can be done while already engaged.

## Bug

`Action_03058` used `NORMAL_CHALLENGE_TYPE`. `stIssueChallenge` auto-engages NORMAL. Pattern A.6 docs wrongly said "no printed engage cost → keep auto-engage" — backwards vs engagement trichotomy (c).

Print has no "Engage your performer" and no En Garde heading. Same seat as Sanjay `_03037` / Stand Your Ground `_04045`.

## Fix

- Mint `COURAGEOUS_CHALLENGE_TYPE = 33` in `Game.php` + JS. Keep **off** auto-engage list. No intervene/Refuse wiring (type exists only to avoid NORMAL).
- `Action_03058` sets that type. Performer filter already had no `!Engaged` (canChallenge does not check Engaged) — left as-is.
- Corrected create-risk A.6 (pattern-a, SKILL, checklist, references) + pattern-b contrasts that claimed Courageous NORMAL auto-engage was correct.

## WHY not reuse SANJAY_CHALLENGE_TYPE

Dedicated card-named types even when they only exist to dodge NORMAL — same discipline as every other never-engage challenge. Avoids accidental Sanjay-keyed handlers later.

## Follow-up: Arrogant same fix

Eddie asked to apply the same pattern to Arrogant `_03008`. Minted `ARROGANT_CHALLENGE_TYPE = 34` off auto-engage; `Action_03008` switched off NORMAL. Updated pattern-a custom-type reasons (item 4 = never-engage), pattern-b contrasts, references. "Leave Arrogant alone" above is obsolete.

## Feelings

Pattern A.6 was written with the wrong half of the trichotomy — classic "your performer issues → NORMAL" trap that Sanjay's journal already warned about for characters. Risk skill lagged behind. Good Eddie caught Arrogant as twin of Courageous rather than leaving the old "Arrogant is basic Challenge" myth in the docs.
