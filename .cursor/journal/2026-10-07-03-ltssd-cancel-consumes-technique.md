# LTSSD cancel still consumed Technique opportunity

## Report
Eddie: Let the Sword Decide canceled Valeri's Technique *effects*, then the UI offered
another Technique. Rules take: cancel effects ≠ undo performing the Technique — it is
like performing with no effect, so no new Technique opportunity.

## Root cause
`argsChooseDuelAction` enables Technique when
`count(duel_round_technique where technique_is_main=1) == 0`.

Cancel path (01146b / 01047 / 03044) deletes Resolve before it INSERTs with is_main=1,
then `recordCanceledAbilityInDuelTable` deliberately INSERTed the canceled row with
`technique_is_main = 0` (see 2026-09-13-11). That left playedTechniquesCount at 0 →
Technique button came back after endOfEvents → DUEL_CHOOSE_ACTION.

WHY the old always-0 was wrong: it treated cancel as "Technique never happened" for
availability. Eddie's ruling is cancel only strips effects.

## Fix
`EventTechniqueCanceled::$countsAsMainTechnique` + factory arg. Opponent cancel sites
read `CHOSEN_TECHNIQUE_IS_MAIN` *before* clearing globals and pass it through so the
canceled duel-table row gets `technique_is_main = 1` when it was the main Technique.

Bastien `duelChooseTechnique_01063` Back keeps default false — player abort is a
take-back (also reopens Used); must not consume the slot. Non-main copies (I Know That
Trick) also pass false via the global.

Torres Cloak (03044) already stored `techniqueWasMain` at offer (globals cleared on
Engage) — pass that into the canceled event on Accept Cancel.

## Supersedes
2026-09-13-11 "technique_is_main stays 0 so main-technique availability counts are
unaffected" — that design goal is reversed for opponent effect-cancel.

## Files
- EventTechniqueCanceled.php, EventFactory.php, EventHub.php, Theah.php
- Reaction_01146b.php, Reaction_01047.php, Reaction_03044.php
- State_duelChooseTechnique_01063.php (comment only; still default false)
