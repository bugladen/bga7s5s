# Justice Served Cold + Unyielding Loyalty handed the technique to the defender

## Bug
Cesca player. Opponent played Justice Served Cold (02049) with Valeri, targeting Ravena. UL (01032) offered on EventCharacterTargeted; Cesca passed. Technique picker then showed Valeri's techniques to Cesca. Back from `highDramaChallengeActionActivateTechnique` goes to choose-target. Status still says they are challenging as Valeri, and no character is selectable.

## Why
Target-chosen events are type=game. `runEvents` only restores `CURRENT_PLAYER` when the state it finishes in is activeplayer (same note already in `stHighDramaInPlayActionDispatch`). UL's reaction transition `changeActivePlayer`s to the defender and that sticks.

`stTechniqueAvailable` then `nextState("hasTechique")` without setting the actor. Techniques are the performer's, so the active player must be `performer->ControllerId` (Defending Honor too — the enemy performer chooses, not `CURRENT_PLAYER`).

Choose-target args filtered `ControllerId != activePlayer`. With the defender active, their own Ravena is hidden. JSC `isValidTargetForAbility` also rejects the challenger's own characters. Empty list. Confirm stays disabled. JSC is not NORMAL/Triskelion/Épée/Cavalier, so the Back button is not even drawn. Hard stuck.

## Fix
- `stTechniqueAvailable`: before `hasTechique`, `changeActivePlayer(performer->ControllerId)`. Not on the no-technique branch — that one already switches to the defender for accept/refuse.
- `argsHighDramaChallengeActionChooseTarget`: filter opponents of the performer controller, not the active player. In-flight table: after Studio upload + refresh, Ravena is selectable again. Cesca confirms her (UL may offer once more; pass). Technique then opens for Valeri's controller.

Did not `changeActivePlayer` inside args. Args run on refresh without rebroadcasting a new state payload to the other client, so the opponent can be "active" with the old empty id list. A click that re-enters target-chosen events is the recovery that actually republishes state.

## Do not regress
- Do not "fix" this by restoring `CURRENT_PLAYER` in `stTechniqueAvailable`. Defending Honor's challenger is the enemy performer; `CURRENT_PLAYER` is the Montaigne who played the Risk.
- noTechnique must still end on the defender.
