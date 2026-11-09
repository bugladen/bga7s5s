# CAD Valeri Technique — Parry restored

## Ask
Technique_05DabneyUS01 now includes +1 Parry as a choice again (print flipped back).

## Context
2026-10-20-06 removed Parry (Riposte/Lethal only; id 1 left unused). Card Text already said "+1[Parry], +1[Riposte], or gain Lethal" — implementation lagged.

## What I did
- Restored `CHOICE_PARRY = 1` on `Technique_05DabneyUS01` (same ids as Pattern E: 0 Riposte / 1 Parry / 2 Lethal — WHY keep ids: Choice persists; renumbering would silently remap in-flight games).
- Calculate / act / notify Parry branch + dashed-Parry reject via `currentRoundCombatCardsHaveDashedParry()`.
- `State_duelChooseTechnique_05DabneyUS01`: `parryAvailable` + four-way `technique_choices` prompt; zombie Riposte → Parry → Lethal. Kept CAD plain strings (no clienttranslate) — do not reintroduce i18n from the older Parry commit.
- `OnUpdateActionButtons.cad.js`: Parry button (id 1) when `parryAvailable`.
- Skill `references.md` back to three-way wording. Card Text already correct; challenge Pass state unchanged (still duel-only).

## WHY not challenge-time choices
Parry/Riposte still need Calculate; Lethal on challenge threat is a no-op at stat cap. Same duel-only gate as before.

## Notes
No Technique_05DabneyUS01 regression suite (needs duel scaffolding). Action suite still green. Print keeps flip-flopping — worth a technique suite if this gets touched again.

## Deploy
Technique_05DabneyUS01, State_duelChooseTechnique_05DabneyUS01, OnUpdateActionButtons.cad.js.
