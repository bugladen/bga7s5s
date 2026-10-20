# CAD Valeri Technique — Riposte or Lethal only (Parry removed)

## Ask
Technique_05DabneyUS01 changed to only allow Riposte or Lethal.

## What I did
- `Technique_05DabneyUS01`: removed `CHOICE_PARRY`, the Parry branch in Calculate, the Parry validation (dashed-Parry check), and the Parry notify. Name -> "+1 Riposte or gain Lethal".
- `State_duelChooseTechnique_05DabneyUS01`: args now only `riposteAvailable` + `technique_choices` ("+1 Riposte or Lethal" / "Lethal" when dashed). Zombie falls back Riposte -> Lethal.
- `OnUpdateActionButtons.cad.js`: dropped the Parry button.
- `_05DabneyUS01` Text updated to "+1[Riposte] or gain Lethal" (it still said Parry; I assumed the print changed and the Text should follow — same "Text is the source of truth" rule as the non-Leader flip-flops).
- Skill `references.md` lines updated.

## WHY
- Kept Lethal = 2 and Riposte = 0 and left id 1 unused (comment in code): the Choice is persisted on the card, and an in-flight game / stale client could still send ids. Renumbering would silently change meaning. An id of 1 now throws "Invalid Technique choice".
- Did NOT remove `currentRoundCombatCardsHaveDashedParry` on Theah — it's shared with other cards.

## Notes
Full suite: 5026 pass. There is no Technique_05DabneyUS01 test (needs duel scaffolding); didn't add one. The print flip-flops often (Thrust -> Parry -> now no Parry) so a test would be worthwhile if this gets touched again.

## Deploy
Technique_05DabneyUS01, State_duelChooseTechnique_05DabneyUS01, _05DabneyUS01 (Text), OnUpdateActionButtons.cad.js.
