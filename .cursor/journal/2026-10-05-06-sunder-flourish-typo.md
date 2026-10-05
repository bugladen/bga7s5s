# Sunder (_01142) — Flourish typo

## Symptom
User: Sunder is a Flourish; Mori Daichi's Technique grants +1 Riposte if combat card is Flourish or Sorcery. Implied: Daichi couldn't get the bonus off Sunder.

## Root cause
`_01142.php` Traits had `Flouish` (typo) instead of `Flourish`. Only occurrence in the repo.

Daichi's gate (`Technique_03050`) uses `$combatCard->hasTrait("Flourish")` — exact string match against TraitNames / hasTrait. Typo → trait miss → technique unavailable.

## Fix
Corrected to `clienttranslate('Flourish')` to match other Flourishes (e.g. `_01051`).

## WHY not change Daichi
Daichi is correct; Sunder was wrong. No fuzzy/alias needed.
