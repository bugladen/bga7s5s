# Action_01071 (Épée Sanglante) — wound types for Renown steal

User asked what kinds of wounds allow Renown steal, then asked to fix so only wounding the duel adversary counts.

## Prior answer (pre-fix)

No wound-type filter. First any-character wound event in that duel won the steal. Card text ("first participant to wound their adversary") was broader than printed.

## Fix (this session)

User: self-wound must not steal Renown from the owning player.

`EventCharacterWounded` handler now requires:
1. `sourceId` resolves to a Character
2. that character has `DUEL_CHALLENGER` or `DUEL_DEFENDER`
3. `getDuelOpponentId(agressor) == woundedCharacter->Id`

Self-wounds / non-character sources / bystander wounds: ignore and do **not** set `firstWoundOccured` (so a later real adversary wound still steals).

WHY conditions instead of only `sourceId != characterId`: `getDuelOpponentId` on a non-participant falls through to `challenger_id`, which could false-positive if an outsider wounds the challenger. Challenger/Defender conditions also survive intervene swaps.

Leftover-threat EOR wounds still count: sourceId = round adversary, wounded = actor — both participants, adversary relationship holds.
