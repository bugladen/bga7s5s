# Giacinto (_04032) En Garde City Action — announce target

## Bug
City Action target pick never hit the game log. Opponent gets the reveal/pass
prompt, but anyone who blinked (or a spectator) only saw that Giacinto's action
fired — not who was targeted. Move announce later only fires if they Pass.

## Fix
`Action_04032::actFromActionWithId` on `04032` (target pick), after validation
and CHOSEN_TARGET set, before queuing `04032_2`:

`${player_name} uses ${card_inject_code} targeting ${character_inject_code}.`

Same copy as Action_04028 / 04040 / 01138 CharacterTargeted window. card_inject
is Giacinto (owner character), not the action composite — matches those siblings.

WHY announce on pick not on resolve: the interesting UX gap is the multi-player
hand-response window. By then the target choice is already locked; naming it
there is what helps people follow the table. Intent language not needed —
target can't be canceled the way Pull's CardMoving can (UL).

## Tests
Action_04032_Test happy path asserts a "targeting" notify with foe + Giacinto
inject codes right after actFromActionWithId on the pick state.

## Follow-up: city-deck Giacinto (_01205) Kidnap

Same gap — EventCharacterTargeted does not notify; Action_01205 never added the
manual line. Engages/moves name characters later if effects resolve, but UL /
cancel and spectators during location commit had no target line.

Announce on **location commit** (`actFromActionWithIds` / `01205_2`), not victim
pick. WHY: that state has Back — an early announce would go stale if they
re-choose. Sits next to CharacterTargeted queue, same copy as 01138/04040.

Action_01205_Test location-choice case asserts the targeting notify injects.
