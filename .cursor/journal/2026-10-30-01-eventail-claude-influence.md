# Éventail + Leader Claude Influence (dusk engarde)

## Report
User: Evantail not giving Inf bonus after Leader Claude en gardes.

Leader Claude = CAD `_05Dandy` (Diplomat, can wear Éventail `_02027`).

## Root cause
Dusk (`stDuskPhaseCleanup`) queues `EventCardMoving` home then `EventCardEngarded` per character. `EventCardMoving`'s hub **appends** `EventCardMoved`, so Engarde runs **while still at the city**, then home `CardMoved` lands later.

That Engarde-before-Moved order is intentional for Nazem (`2026-03-31-09`) — enemies must still be at the site when they en garde. Do **not** demote dusk Engarde after Moved.

Éventail's engarde +1 writes `ModifiedInfluence` immediately but only updates Claude's `UnclampedInfluence` via deferred `EventCharacterInfluenceModified`. Claude's home `CardMoved` calls `syncRenownPenalty`, which rebuilds the chip from Unclamped. If Renown was at the city:

1. Engarde: chip 3→4 (Eventail), Unclamped still 3, InfMod queued
2. Home Moved: Unclamped 3+1renown→4, chip forced to 4 — **Eventail +1 wiped**
3. InfMod Hub sets NewInfluence=4 (stale) — chip stays wrong; or if Unclamped was later bumped, desync

With 0 Renown the sync early-returns and the bug is invisible — matches "after Claude en gardes" at dusk leaving a Renown site.

## Fix
`_02027::modifyInfluence` when host is `_05Dandy`:
1. `absorbImmediateInfluenceDelta($amount)` — Unclamped += amount now; `SkipExternalUnclampedInfluenceEvents++`
2. `stackEvent` the InfMod (Claude path only) so the notif drains before home Moved and Hub does not re-apply a stale NewInfluence over the restored chip
3. Claude's InfMod handler skips Unclamped when SkipExternal… > 0 (consumes one) so absorb + InfMod do not double-count

Non-Claude hosts still `queueEvent` InfMod as before.

## Not done / do not "simplify"
- Do not move dusk Engarde after Moved to "fix ordering" — breaks Nazem.
- Do not fold Éventail into `InfluenceModifier` toggles without checking Claude-before-Eventail card iteration order on the same event.

## Tests
`Card_05Dandy_Test`: two dusk-race cases (InfMod drained before move; move before InfMod drains).
