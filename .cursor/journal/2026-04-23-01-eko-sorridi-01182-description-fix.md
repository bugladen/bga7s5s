# Eko Sorridi (01182) — Reaction Target Array + Character Name

## Bug

When multiple opposing characters leave Eko's location (e.g., during Dusk), each `EventCardMoving` overwrote the single `$TargetCharacterId`. All queued reaction prompts showed the last character's name, and wounding targeted the wrong character.

## Fix

Converted `TargetCharacterId` (int) → `TargetCharacterIds` (array), following the same pattern as Reaction_01181 (Sorte Deck's `HealTargetIds`).

- **handleEvent**: pushes each character ID onto the array (`$this->TargetCharacterIds[] = $event->cardId`)
- **getReactionDescription**: looks up `$this->TargetCharacterIds[0]` to show the current target's name
- **performReaction (woundCharacter)**: `array_shift` to pop the current target, clears remaining IDs, calls `deleteTransitionEvents` to clean up queued transitions, then sets used
- **performReaction (pass)**: `array_shift` to remove current target so the next queued transition shows the correct character

## WHY array_shift + deleteTransitionEvents on wound

When the player chooses to wound, the reaction is used (`setUsed`). Any remaining queued transitions for other characters are now irrelevant since `isAvailable()` will return false. But those transition events are already in the DB queue, so `deleteTransitionEvents` cleans them up explicitly (same as Reaction_01181 does). On pass, we just shift the current target off — the next transition in the queue handles the next character naturally.

## WHY IsUpdated on pass

The array mutation needs to be persisted to DB. Without `$ekko->IsUpdated = true`, the shifted array wouldn't be saved, and the next reaction prompt would still show the old first element.
