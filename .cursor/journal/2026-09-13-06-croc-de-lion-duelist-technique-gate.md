# Croc de Lion (02026) — Duelist Technique availability gate

## Change

`Technique_02026a` and `Technique_02026b` `isAvailableToPlayer`: after IN_DUEL check, require equipped character (`getOwningCharacter`) has trait `Duelist`.

## WHY

Card text is **Duelist Technique**, not "may only equip to Duelist". Prior session (`2026-03-30-02`) correctly removed equip restriction per Eddie — that stays. Keyword still means the *ability* is only for Duelists while equipped.

Same pattern as Duelist Maneuvers (`Maneuver_01077`, `Maneuver_01084`, etc.) but check equipped character rather than `getDuelRoundActor` — user asked for equipped-character gate, and for an attachment technique that's the precise subject of the keyword.

## Not done

No equip restriction re-added on `_02026`. Don't "fix" that unless card text changes.
