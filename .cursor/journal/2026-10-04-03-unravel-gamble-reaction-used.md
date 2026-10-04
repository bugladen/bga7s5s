# Unravel the Thread Reaction not triggering on gamble (Cesca)

## Report
Eddie: Unravel (`Reaction_04010`) does not trigger when gambling. Actor was Cesca (`_03001`) — Sorcerer, so the trait gate is not the miss.

## Root cause
Same class as Lodestone Used-after-sink (`2026-10-04-02`):

1. On Use, `setUsed(true)` persists on the Risk while it stays in `Faction-*` (gamble is a peek; unchosen sink keeps it in the deck).
2. `CardReaction` only clears `Used` on `EventDuskEndOfDay`.
3. `buildCity()` deliberately omits faction decks → dusk never reached deck cards until `deliverDuskEndOfDayToFactionDecks` (same commit as Lodestone).
4. Next gamble reveal: `isAvailable()` false → no `"04010"` transition → straight to choose combat card.

Cesca being the actor ruled out "forgot Sorcerer check" / wrong performer. Sep 29 journal proves Use *did* work once; stuck Used explains later silence.

## Fix
`Reaction_04010::handleEvent` on `EventDuelGambleCardsRevealed`:

1. If this card is in `revealedCardIds` and `Used`, soft-clear (`Used=false`, `IsUpdated`) before `isAvailable()`.
   - WHY heal here (not only dusk): mid-game copies already stuck need to offer today; dusk pass only helps after the next dusk.
   - WHY revealedCardIds gate: proves we're on the deck edge now. Same-day re-offer after Use needs empty-deck reshuffle (rare; accept like Lodestone heal).
2. Owner player id = `ControllerId ?: OwnerId` for the actor-controller match and transition playerId.
   - WHY: deck Risks can have `ControllerId=0` while `OwnerId` is the faction owner; "your performer" is ownership.

Systemic dusk→faction-decks already shipped in Lodestone commit; this is the Reaction availability heal parallel to `Action_03065::isAvailableToPlayer`.

## Not done
- Generic heal for every deck-card CardReaction — only Unravel lives on a peeked Risk today (Pattern D.5).

## Other EventDuelGambleCardsRevealed listeners (not D.5)
Eddie asked whether others share this pattern. Answer: **no other Pattern D.5**.

| Card | Class | Why not the same bug |
|---|---|---|
| Unravel `_04010` | `Reaction_04010` on the peeked Risk | **Only D.5** — Used sticks in Faction-* |
| Ivy `_02042` | `Reaction_02042` on in-city Character | Lives in `buildCity()`; dusk clears Used normally |
| Proper Drama `_03047` | `Maneuver_03047a` | Maneuver (once/duel), not CardReaction on deck card |

Printed "reveals this card while gambling" exists only on `_04010`. Skill Pattern D.5 sole reference is Unravel.

## Sink-self / Used-stuck audit (Eddie follow-up)

Dusk `Used` + ends in Faction-*:
| Card | Ability | Notes |
|---|---|---|
| Lodestone `_03065` | `Action_03065` | Heal + dusk pass |
| Unravel `_04010` | `Action_04010` | dusk pass (hand+Used same day = correct once/day) |
| Unravel `_04010` | `Reaction_04010` | Heal on reveal + dusk pass |

Other sink-self:
| Card | Why different / residual risk |
|---|---|
| **Dame of Swords `_02055` `Technique_02055`** | Sink self → Faction-*. Technique defaults `ResetOnDuelEnd=true`, `ResetOnDayEnd=false`. `EventDuelEnd` never reaches faction decks; **dusk pass does NOT clear Technique Used**. Re-equip → `mustBeAvailable` hides it forever. **Still broken** — needs duel-end→deck pass, ResetOnDayEnd, or Lodestone-style heal. |
| Penya `_04cd01` `Action_04cd01b` | Sink self → **City** Deck (not faction) |
| Locker unique spends | Self→Locker; Unique/gone |

Dusk-Action/Reaction faction-deck cases: only Lodestone + Unravel (Action+Reaction). Dame is a Technique-Used variant of the same sink-to-deck hole.
