# Adaptable gambled — opponent "played" it, Maneuver missing

## Report
Intervene with Iñigo, gamble Adaptable. Maneuver not offered. Log says opponent played Adaptable.
Follow-up: prior day opponent Improvised that Adaptable; reporter then had Adaptable in hand AND gambled another — two copies that Unique deck validation would never allow.

## Root cause (Improvising shell stuck in discard)

`EventCardDiscardedFromHand` has `runEventHubAfterCards=true`. `_01106_RiskClone` (and 01124/01154/04cd01) ran cleanup in card handleEvent **before** the hub moved the shell into OwnerId's discard:

1. Card handler: `moveCard(…, Permanently Hidden)` + queue remove-from-discard (without `permanentlyHide`)
2. Hub: `moveCard(…, Discard-OwnerId)` — **undoes the hide**
3. Later remove event: notify only (`permanentlyHide=false`) — shell stays in owner's discard
4. Reshuffle: shell enters faction deck. `card_type` is `01106_RiskClone` so Unique validation never sees it. UI shows Adaptable via `RiskClonePropertyTrait`. No Maneuvers on the shell. `ControllerId` remains the Improvising thief → combat-card log names the opponent.

## Fix
### Shell cleanup order
- 01106 / 01124 / 01154 / 04cd01 RiskClones: drop immediate hide; queue `createCardRemovedFromPlayerDiscardPileEvent(…, permanentlyHide: true)` so hide runs **after** the discard hub write.
- Shuffle safety: `isTemporaryRiskCloneShell()` shells are Permanently Hidden instead of decked.

### Controller attribution (still needed for other stale paths)
1. Combat play (gamble/hand/Broken Time): set ControllerId to actor + clear Used flags.
2. EventHub combat-stats notify: prefer duel-round actor ControllerId.
3. EventCardAddedToHand / FactionDeck: set ControllerId from event playerId.
4. Normal discard reshuffle: `ControllerId = OwnerId ?: playerId`.
5. Mireli free-gamble: actor ControllerId, not card.

## WHY not only fix ControllerId on gamble
That would rename the log line but leave a Unique-looking shell with **no Maneuver** in the deck forever. The shell must never survive into the faction deck.

## Tests
`Card_01106_RiskClone` (permanentlyHide queue + hub order), `Card_04cd01_RiskClone` updated, shuffle hides shells, prior FrameworkActions_Duel stale-ControllerId case.
