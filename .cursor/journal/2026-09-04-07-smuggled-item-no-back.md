# Smuggled Item (01187) — no back on choose attachment location

## Ask
User: arriving at `highDramaEquipActionChooseAttachmentLocation` from `Action_01187` must not show a back arrow.

Follow-up: keep `backSmuggledItem` for zombie; zombie purpose is to unwind to a passable state, not advance deeper into equip.

## WHY
`Action_01187` jumps here after the in-play action is already confirmed. Human players must not get a back arrow (no safe prior chooser in the UI sense).

Zombie is different: it needs an abort path. `backSmuggledItem` → `HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION` lets it climb out toward pass. Advancing into hand/play choosers was the wrong instinct — Eddie corrected that.

## Changes
- `OnUpdateActionButtons.js`: only add `<` for `NORMAL_EQUIP_TYPE` (humans)
- `states.inc.php`: keep `actBackWithTransition` / `backSmuggledItem` (zombie-only abort)
- `ZombieTrait`: SMUGGLED_ITEM → `actBackWithTransition('backSmuggledItem')`; normal equip → `actBack`
