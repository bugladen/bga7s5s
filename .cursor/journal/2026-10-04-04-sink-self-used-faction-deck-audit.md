# Audit: sink-self / faction-deck Used vs EventDuskEndOfDay

Parent asked for every ability that sets CardAction/CardReaction Used then leaves the ability card in Faction-* (or Locker) such that pre-`deliverDuskEndOfDayToFactionDecks` dusk would miss the clear.

## Real dusk-Used hits (faction deck)

| Card | Ability | Used on | Ends up | Dusk pass enough? | Mid-game heal? |
|---|---|---|---|---|---|
| `_03065` Lodestone | `Action_03065` AttachmentAction | Action Used (confirm) | Faction deck bottom (sink self) | Yes going forward | **Yes already** — equipped+Used can only be stale |
| `_04010` Unravel | `Action_04010` RiskAction | Action Used | Faction deck bottom (sink self) | Yes going forward | No Lodestone-style heal; hand+Used same-day after sink is legitimate once/day |
| `_04010` Unravel | `Reaction_04010` CardReaction | Reaction Used | **Stays** Faction-* (gamble peek) | Yes going forward | **Yes already** — heal on reveal when in revealedCardIds |

## Checked, not dusk-Used hits

- **`Technique_02055` Dame of Swords** — sinks self to faction deck, but Technique Used is once-per-duel (`EventDuelEnd`), not dusk. Excluded by criteria. Analogous miss: `EventDuelEnd` is also not delivered to faction decks; dusk pass does **not** fix Dame Technique Used.
- **`Action_04cd01b` Penya** — sink self to **City** Deck. Excluded from faction dusk audit, but same stuck-Used class for City Deck. **Fixed 2026-10-04:** clear before sink + equip/`isAvailableToPlayer` heals (see journal 03).
- **Clones** (`_01106_RiskClone`, `_04cd01_RiskClone`, `_01154_RiskClone`) — temporary / parent locker. Excluded.
- **Look-at-deck sinks of other cards** (Otto, Gustavo, Monet, etc.) — not self.
- **Locker unique spends** (`Action_01168`, `Action_03067`, `Action_02051`, `_01111`, `_01139`, `_01154` via clone, `Reaction_01202`) — CardAction/CardReaction Used + self→Locker. `deliverDuskEndOfDayToFactionDecks` does **not** cover Locker. Practically N/A: Unique / gone, no redraw path.
- **`_02037` Mysta Forced** — sinks self on discard (not as part of Reaction resolve). `Reaction_02037` can leave Used=true in play; later Forced can carry that into Faction-*. Soft path; dusk pass clears across days; equipped+Used same day is legitimate (no Lodestone heal).

## Exhaustive search notes

- All `createCardAddedToFactionDeckEvent` call sites reviewed.
- Printed "Sink this card" texts: only `_03065`, `_02055`, `_04cd01` (+ Action_04010 "sink this card" in B.4).
- Only Pattern D.5 deck-card CardReaction is `Reaction_04010` (journal 03 already).
- No Maneuver sinks its own ability-bearing card into faction deck with dusk Used.

## Verdict

Only **three** real dusk-Used / faction-deck cases — the three already known. Dame is the only other faction-deck sink-self, but duel-reset not dusk.
