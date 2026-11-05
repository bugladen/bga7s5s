# Frau Eleanor + Well-Equipped

User: Eleanor couldn't use Well-Equipped. She's supposed to count as equipped with a Ranged Weapon; Well-Equipped En Gardes an equipped performer.

## Cause

Eleanor's fiction only lived in `countsAsEquippedWithRangedWeapon` (wired into Long Shot / Vantage Point). `Action_01061` / `Maneuver_01061` still raw-checked `$character->Attachments` / Weapon trait loops. Empty Attachments → Action unavailable even for her controller.

## Fix

Added on `Character`:

- `countsAsEquipped($theah, $actingPlayerId)` — any attachment OR ranged fiction
- `countsAsEquippedWithWeapon($theah, $actingPlayerId)` — real Weapon OR ranged fiction

WHY chain through the ranged hook rather than Eleanor overriding three methods: her printed line is specifically Ranged Weapon, and Ranged Weapon ⊆ Weapon ⊆ equipped. One override stays the fiction source.

WHY not invert "unequipped" gates (`Action_01131` etc.): skill/"may" is permission for if-equipped *benefits*. Forcing her out of unequipped-performer abilities would be a downside without a choice prompt.

Wired Action_01061 availability/performers/trigger and Maneuver_01061 draw through those helpers. Updated create-character skill/checklist/pattern-a call sites. Regression cases for engaged Eleanor with no attachment on both Action and Maneuver.

## Correction

There was no pre-existing Engage/En Garde bug. Original already called `createCardEngardedEvent`. I typo'd `Engaged` during the rewrite, saw the test fail, "fixed" it back, and wrongly blamed HEAD. User caught it. Comment removed.
