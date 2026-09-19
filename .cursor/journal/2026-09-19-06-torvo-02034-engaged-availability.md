# Torvo Action_02034 unavailable while Engaged

## Report
Eddie: Action_02034 not available. Torvo engaged; two opposing characters with 3 Combat at his location.

## Cause
`getSelectableOpponentCharacterIds` filtered `! $c->Engaged && ModifiedCombat >= 2`. Card text only requires opposing + Combat ≥ 2. Torvo's own Engaged is correctly unchecked (no Engage cost on the City Action).

April journal called unengaged targets a "tac convention" — wrong for this card. Same "They may X. If they do not, Y" shape as Duckfoot 01049: engaged targets are still legal (decline → draw).

## Context that makes Engaged targets safe to accept
Aug 2026 commit `6f7c85f8` removed `TORVO_ESPADA` from `stIssueChallenge` auto-engage ("Action no longer forces adversary to engage"). So an already-engaged opponent accepting does not re-fire `EventCardEngaged`. Do **not** put TORVO back on the auto-engage list without revisiting that decision.

## Fix
- Drop `! Engaged` from the selectable filter; keep `ModifiedCombat >= 2`.
- `getArgs` for step 1 uses `getOwningCharacter` instead of `CHOSEN_PERFORMER` (same result for CharacterAction, fewer moving parts).

## Not changed
Decline → draw path, intervene block via `TORVO_ESPADA_CHALLENGE_TYPE`, technique.
