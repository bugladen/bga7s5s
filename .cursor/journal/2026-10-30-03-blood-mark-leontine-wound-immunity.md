# Blood Mark / Leontine / Altruistic leftover threat

## Report (updated with log)
gregfilip (Montaigne) vs Galaren66 (Cesca Leader). Blood Mark moved Léontine+Odette Home→Forum; Léontine took BM wound.

Cesca Action_03001 move-wound: heal logged, Léontine no wound — **not a bug**. Log line present:
`gregfilip used Reaction to ignore the wound on …` + played card = **Cascade Reaction_02059**. Working as printed.

Veronica's Guile Influence duel, Cesca Inf 4:
- R1 Léontine actor: Riposte+2 + Maneuver_04060 Thrust → ending D2/C3. **No leftover wound log.**
- R2 Cesca: leftover 2 applied.
- R3 Léontine: Riposte+1 maneuver cleared her pool to 0 — no leftover expected.
- R4 Cesca: leftover 2 applied.

So only R1 leftover vanished. Blood Mark left no immunity.

## Root cause: Altruistic Reaction_03031
Latent sibling of Shield Rite (journal 2026-10-03-04).

1. `isOpponentAbility` was ControllerId-only. Leftover threat = `sourceId=adversary`, `abilityId=''` → treated as opponent ability.
2. Offer needs another performer at location — Odette was at Forum with Léontine after Blood Mark. Perfect setup.
3. **Decline of wound/move/engage did not re-release** (only intervene Decline did). Cancel-first left the wound permanently canceled. No decline log line → matches "I saw no ignore/cancel" if they hit Decline on a blank-named redirect prompt.

## Fix (Reaction_03031)
1. Early-return false in `isOpponentAbility` when `abilityId === ''` (Shield Rite / Cascade / Kaspar shape).
2. Decline re-releases held wound/move/engage onto original target + `skipNextEvent` (same as intervene Decline / Cascade Pass).

## Tests
Reaction_03031: decline re-releases; empty abilityId leftover threat does not offer. 7/7 pass.

## Confirmed
Eddie: gregfilip had Altruistic in hand and Declined the R1 leftover redirect. Exact failure mode.

## WHY not Blood Mark / Cesca Action
No sticky flag on Action_01076. Cesca heal-then-wound log shape was Cascade canceling the wound half after the announce. Round 3 leftover silence was correct (pool 0 after Riposte maneuver).
