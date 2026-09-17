# Concealed Flintlock (_02054) Technique wound not applying

## Context
User asked to ensure Maneuver_02054 inflicts a wound on the choice option. Mis-tagged: Maneuver only equips (+ optional draw). The may-suffer-wound / else +1 Parry choice is Technique_02054.

## Bugs found (both in `actFromTechniqueWithId`)

### 1. Wrong id to `getDuelOpponentId`
Was: `getDuelOpponentId($owner->ControllerId)` — player id.
`getDuelOpponentId` compares against `duel.challenger_id` / `defender_id` (character ids). Player id never matches → always returns `challenger_id`.

When Flintlock's controller is the challenger, "Suffer a Wound" wounds their own participant instead of the adversary. Matches the Maneuver_01110 audit (2026-05-07-02).

Fix: `getDuelRoundActor()->Id` then `getDuelOpponentId($actor->Id)`.

### 2. `AdversarySufferedWound` lost across state transition
Choice sets flag + `IsUpdated`, then `nextState()` → `DUEL_CHOOSE_TECHNIQUE_EVENTS` → `stRunEvents` → `buildCity()` before `runEvents`. IsUpdated only flushes at end of each event inside runEvents, so rebuild wiped the flag. Result: wound might fire (once targeting fixed) but calc still saw `AdversarySufferedWound = false` and added +1 Parry.

Fix: immediate `$game->updateCardObjectInDb($owner)` after setting the flag (same WHY as Breastplate _01153).

## Not changed
Maneuver_02054 — no wound option on printed text; left alone.
