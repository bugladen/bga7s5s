# Axelle Reaction_04022 — Home / opposing gate bug

## Bug
Axelle at Home could fire her Reaction when an adversary announced a combat card in a City duel. Text: "after an **opposing** adversary announces their combat card." No opposing characters at Home.

## Root cause
Original gates only checked announcer ≠ owner controller + owner controller has a duel participant. Treated "opposing adversary" as "the other duel side," not location-scoped opposing. Same miss as would hit any "opposing" ability that skips `cardInCity` / location match.

## Fix
`Reaction_04022` now also requires:
1. `cardInCity($owner)` — Home short-circuit (shared `LOCATION_PLAYER_HOME`; Benci/Axelle aura pattern)
2. Owner at duel location (`Location` matches challenger or defender) — Andare `Reaction_04031` shape

Re-checked in `performReaction` so mid-flow move-off doesn't resolve illegally.

## WHY not only location-equality
Location equality alone would usually fail Home (Home ≠ City duel site). `cardInCity` is still required for the established opposing-at-Home brokenness and to match Pattern C / Kaj / Daniella short-circuits — don't rely on duel-only geography.

## Docs
Updated create-character SKILL shape row + pattern-d "Adversary announces combat card → threat" gates. Original Axelle journal (`2026-08-22-03`) listed incomplete gates — this entry is the correction.

## Unfinished / test
- Axelle at Home during City duel → no Reaction prompt on adversary combat-card announce
- Axelle at duel location (not necessarily participant) → still prompts
- Axelle at a different City location → no prompt
