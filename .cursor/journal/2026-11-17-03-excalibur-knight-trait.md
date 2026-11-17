# _05Excalibur Knight trait grant

## Context

Prior session (2026-11-10-08) finished Dietrich Blade Reaction + Technique. Text now also says equipped character gains Knight. Trait name already existed in TraitNames (tac Knights).

## Change

Pattern B passive trait grant on `_05Excalibur` — Equipped adds Knight, Unequipped removes. Mirrored Kaiser `_03019` Hunter pair.

WHY both halves: unequip without removeTrait leaks Knight after the blade leaves (destroy / re-equip elsewhere / host death unequip).

No new GameState/JS. No clienttranslate (CAD prototype plain strings).

## Tests

`Card_05Excalibur_Test` — equip grants / unequip removes (Kaiser shape).
