# _05Robin (Rogue's Cowl) - CAD faction attachment

Last session context: Dabney technique/riposte work (journal 06). Not related.

## What I did
- `_05Robin` now `implements IHasTechniques` + `TechniqueTrait`, registers `Technique_05Robin` (new, `cad/techniques/`).
- Covert gamble +1 reveal: `getNumberOfGambleCardsToReveal` override (Gallegos `_01101` / `_04017` shape) plus a Covert gate:
  host must be in a City location (`cardInCity`) and `!getCityLocation(...)->isControlled()`.
  WHY gate in the override and not elsewhere: Covert reminder text says "uncontrolled locations"; same rule used by Forsythe / Overcoat / Valeri.
  Home is not a City location, so it never counts as uncontrolled - consistent with the other Covert cards.
- Technique: "If your combat card is a Flourish or Stealth card • +1 Thrust" - the If-clause is before the bullet, so it's the availability gate
  (mirrors `Technique_03050`: `getCombatCardsForCurrentRound()` filtered by controller). IN_DUEL gated since "combat card" doesn't exist in challenges.
  No engage cost printed -> attachment not engaged. Own class, not `Technique_PlusOneThrust`+setId, because PlusOneThrust has no combat-card gate.
  Robin itself has Stealth, so playing Robin as the combat card satisfies the Technique.

## Decisions / oddities
- User said: test card, NO translate functions front or back. So plain strings everywhere (no `clienttranslate`, no `$game->translate`), even in
  explanations. Later pass will add translations - don't "fix" this before they ask.
- Set `WealthCost = 0` explicitly (card art shows 0 cost).
- Text had `[thrust]`; changed to `[Thrust]` to match other cards' icon token casing (`_04054`).
- Left the trailing whitespace line in Text alone (user-authored).

## Status
php -l clean, `php tests/run.php` 5026 pass. CAD cards aren't covered by regression suites; no new suite added (not tested in-game).
No JS / state work needed (no picker).

## Skill update
Updated create-faction-attachment (SKILL.md shape table, pattern-b Covert passive, pattern-e combat-card-trait Technique, checklist 16h-16k, references, helpers, wiring, compose) with Robin lessons: passive Covert gate inside gamble override (cardInCity before getCityLocation - throws for Home), combat-card trait availability gate, read WealthCost from art, [Thrust] casing, and that 'no translate' for prototype cards also covers translate() in explanations.
