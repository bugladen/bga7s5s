# CAD prototype cards — strip translation wrappers

## Ask
User wants test/prototype CAD classes obfuscated from BGA translators: no `clienttranslate()`, `$game->translate`, or JS `_()` on their strings.

Cards: 05DabneyUS01, 05Cooper, 05Coleman, 05Henry, 05Thomas, 05Fedora, 05Gary (+ flipped composites).

## Audit result

**Already clean (plain Name/Title/Text/UserException/notify):**
- All `_05*.php` card classes under `cards/cad/`
- All composites: Action/Reaction/Technique/Maneuver for these ids
- Gary setup + flipped duel states already documented as plain-string
- `OnEnteringState.cad.js` choose-list labels already plain

**Had wrappers / i18n exposure — fixed:**
1. `State_duelChooseTechnique_05Cooper.php` — still had `clienttranslate` on description + descriptionMyTurn (missed when other CAD states were stripped). Removed.
2. `OnUpdateActionButtons.cad.js` — every CAD handler button used `_()` (Confirm, +1 Parry/Riposte, Lethal, Pass, Ok, Confirm Selection). Stripped to plain strings.
3. `State_duelChooseTechnique_05DabneyUS01.php` — `getArgs` had `'i18n' => ['technique_choices']` which tells the client to run those values through translation lookup. Removed i18n key; technique choice strings were already plain English. Stale WHY comment mentioned clienttranslate — updated.

## WHY strip i18n arg too
Even without `clienttranslate` on the PHP side, `'i18n' => [...]` is the client-side hook that applies `_()` to those arg fields. Leaving it would still treat CAD technique prompts as translatable UI. Match the "hide from translators" goal fully.

## Not touched (out of scope / already correct)
- Base-set `_01123` Valeri / `_01040` Rena / `_01013` Vissenta still use `clienttranslate` — those are published cards, not CAD prototypes.
- Notify message English without wrappers — BGA extractors key off `clienttranslate`/`_()`, so plain notify strings stay out of translator queues.

## Unfinished
None for this ask. Re-check any new CAD state/JS when adding more 05* prototypes.
