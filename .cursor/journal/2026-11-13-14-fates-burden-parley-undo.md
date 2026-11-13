# Fate's Burden falls off on Parley rewind

## Report
Opponent engaged for Parley, didn't confirm, rewound to use Kaspar city action. Fate's Burden fell off when the game stood the character back up.

## Root cause
`stRecruitUndoEngage` / `stRecruitUndoEngageToMerc` fire a real `EventCardEngarded` to refund the Parley engage cost. `_01025_Burden` Forced is "when would en garde • Destroy this instead" and treated that refund as a game-effect En Garde → canceled Engarded + `removeRiskAttachment`.

Same shape on Legion's Caress `_01021` ("After … en gardes • Wound them") — would wound on Parley Back.

## Fix
1. `EventCardEngarded::$asCostUndo` (default false)
2. Both recruit undo states set `asCostUndo = true` before queue/run
3. Burden + Caress skip when `asCostUndo`

## WHY not skip handleEvent entirely for cost-undo Engarded
Eventail `_02027` (+1 Inf while en garde) and `_01119` (opposing-engaged Influence sync) still need the Engarded: Parley engage already stripped Eventail's bonus, so the undo Engarded must restore it. Flag lets Forced/"would en garde" effects ignore the refund without breaking continuous sync.

## WHY reactions were already safe
UL / Vittoria / Cross gate on `shouldReactToEvent` needing a targeting ability. Framework undo Engarded has sourceId=0 / abilityId="" so they never intercept. Claim has no undo-engage path (commit on performer chosen).

## Tests
- `Card_01025_Burden_Test`: asCostUndo does not unequip
- `Card_01021_Test`: asCostUndo does not wound
- `StatesTrait_HighDrama_Test`: both undo states stamp asCostUndo

40 green across those three suites.
