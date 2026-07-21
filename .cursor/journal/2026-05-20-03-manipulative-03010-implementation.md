# Manipulative (_03010) — Implementation

Vodacce Risk with a single Strega Reaction. Combat stats 1/1/2, Wealth 0. Traits: Hubris, Sorcery, Sorte.

## Card Text

> **Strega Reaction:** After target character is mustered from an Approach deck • Wound them unless their controller returns that character to their Approach deck and musters a different character. Wound them again if you control three or more **Strega**.

## Pattern Choices

- **`RiskReaction`** played from hand. Pay cost is the Risk itself (PAY_STATE_IN_HAND_REACTION).
- **Triggers on `EventApproachCharacterPlayed` AND `EventCharacterMustered` (filtered to `fromLocation == LOCATION_APPROACH`).** Both events represent "mustered from an Approach deck": `EventApproachCharacterPlayed` covers the canonical Approach-Phase play; `EventCharacterMustered` covers high-drama card effects like Chance Meeting `_03cd03` and Réputation Méritée `_01072`.
- **Added `string $fromLocation` field to `EventCharacterMustered`.** Populated in `EventHub::handleEvent` before `moveCard` runs, by reading the in-memory `$character->Location`. The hub's central handler runs before card handlers (`runEventHubAfterCards=false`), so by the time my reaction sees the event, `$event->fromLocation` reflects the pre-move source. Without this field, source can't be recovered — the `$character->Location` is already updated to the destination by the time card handlers run.
  - `EventHub.php:854` block: 4-line capture before `moveCard`. Guard: only set `$fromLocation` if currently `''`, so a caller could override if needed (none currently do).
  - Touching the event factory + hub central handler is a framework-level change but it's surgical and unobservable to existing callers (default `''`).
- **"Strega" keyword = performer-trait gate**, *not* a Sorcerer ability. Memory feedback explicitly: only literal "Sorcerer" → `ISorcererAbility`. I count "any Strega character of mine in play" because the card text doesn't pin the performer to a location (contrast Premonition `Reaction_03006` which says "at your performer's location"). One in-play Strega satisfies the gate; the wound is applied from anywhere.

## Multi-Stage Cross-Player Choice

Modeled on `Reaction_03007` (Matushka's Shears) — internal `$stage` field plus chained `createReactionTransitionEvent` calls to bounce active player between owner and opponent. But Matushka's is an AttachmentReaction, no pay state. For a RiskReaction I had to wedge the pay-state in between:

1. `EventApproachCharacterPlayed` → save target + opposing player + queue ReactionTransition to owner (stage='').
2. `performReaction(stage='', 'use')` → queue `EnteringPayState` + `ReactionPayTransition`. (`'pass'` → reset state, owner keeps the Risk.)
3. Framework pays (Risk discarded from hand) → fires `EventRiskReactionTriggered`.
4. `handleEvent` on `EventRiskReactionTriggered` → set stage='choice', queue ReactionTransition for opposing player. If they have NO other Approach character, skip the choice and apply wound immediately + finalize.
5. `performReaction(stage='choice', 'return' | 'accept')`:
   - `'return'` → set stage='pickMuster', queue ReactionTransition for opposing player.
   - `'accept'` → applyWound + finalize.
6. `performReaction(stage='pickMuster', 'muster-{id}')` → validate ≠ target; queue `createCharacterPutIntoApproachDeckEvent` for the target + `createCharacterMusteredEvent` for the chosen character; finalize.

`finalize()` calls `$this->setUsed(...)` and resets state. `'pass'` does not set Used — the Risk stays in hand for future triggers (though in practice the Risk will discard the first time the owner uses it).

## Pre-commit hook compliance

`RiskReaction` requires `Location == Game::LOCATION_HAND` literal. CardReaction parent requires `$this->setUsed(` and `$this->isAvailable(` literals. I had originally written `$owner->Location != Game::LOCATION_HAND` (the negative check) which fails the hook's substring grep — the hook looks for `==` specifically. Refactored to split into two checks so the positive `== Game::LOCATION_HAND` substring appears in source.

## Wound semantics

"Wound them again if you control three or more Strega" → I use a single `createCharacterBeingWoundedEvent` with `$wounds = 2` (vs 1 default). Considered queuing two separate events to match the "again" phrasing literally, but the framework treats wounds=N as N wounds applied in one event — same downstream effect for triggers, simpler in source.

Count of "your Strega" = `getCharactersInPlayByPlayerId($owner->ControllerId)` filtered by `hasTrait('Strega')`. In-play means city OR home, so home-bound Strega count. Computed at the moment of `applyWound` rather than at trigger time, in case the count changes during the choice flow.

## State / JS wiring

**None needed.** The standard `playerReaction` state handles all stages; the reaction's `getReactionButtonProperties()` renders the right buttons per stage. No card-specific state, no JS hooks. The `playerReaction` state exists alongside every phase's events state (per `create-scheme` skill notes), so this works in the Approach Phase too.

## Framework changes (also fix latent bugs in Object of Wonder `_01202`)

`EventCharacterPutIntoApproachDeck`'s EventHub handler (`EventHub.php:1348`) previously only sent a text `"message"` notification and the private `approachCardsReceived` notification. Two gaps Eddie called out:

- **No `cardRemovedFromPlay` notification.** Other players' clients had no way to remove the character from the in-play view. Fixed: when the character was in play (city or `LOCATION_PLAYER_HOME`) at the time of the put-back, send `cardRemovedFromPlay`.
- **In-play state carried over.** A character returning to the Approach deck still had `Wounds`, `Engaged`, `IsDying`, `WoundsHealedIncoming` set from its prior life. A card in the Approach deck has no memory of its prior state — those persist in DB and would re-appear when the character was next mustered. Fixed: reset all four fields in the handler before the in-memory update.

Object of Wonder (`Reaction_01202`) benefits from both fixes — it sends a destroyed character (with Wounds, IsDying=true) back to the Approach deck, and this would have left stale state.

## Files

- `modules/php/cards/faf/_03010.php` — wired `IHasReactions` + `ReactionTrait` + `Reactions = [new Reaction_03010()]`.
- `modules/php/cards/faf/reactions/Reaction_03010.php` — new.
- `modules/php/theah/events/EventCharacterMustered.php` — added `$fromLocation`.
- `modules/php/theah/EventHub.php` — populates `EventCharacterMustered.$fromLocation` before move; on `EventCharacterPutIntoApproachDeck`, sends `cardRemovedFromPlay` when the character was in play and resets `Wounds`/`Engaged`/`IsDying`/`WoundsHealedIncoming`.

Both lint clean. Pre-commit hook passes.

## Things I considered and ruled out

- **`IRiskThatTargetsCharacters`.** Risk only "targets" in the trigger-phrasing sense ("target character is mustered") — there is no player-driven character chooser, no `EventCharacterTargeted` is emitted by the wound flow. Looked at `_01115` (Taunt) which marks the interface because its City Action explicitly targets a character via `IAbilityThatTargetsCharacters`. Manipulative doesn't qualify on that bar. Ruled out.
- **Location filter on Strega gate.** Premonition (`_03006`) restricts to performer's location because its text explicitly says so. Manipulative says nothing about location — any in-play Strega is the gate.
- **Listening to ONLY `EventApproachCharacterPlayed`.** Eddie noted the printed text covers any muster from the Approach deck, so I added `EventCharacterMustered` as a second trigger and threaded a `fromLocation` field through the event so it can be filtered. Captured in the EventHub central handler before the move (default `runEventHubAfterCards=false` means hub runs first, card handlers see the populated field).
- **Custom card-specific state.** Considered modeling on Crimson Roger (`_02036`) two-state flow. Doesn't fit a RiskReaction because reaction flow goes through the framework's pay state, and there's no clean way to interject card-specific state mid-reaction. Stage field + chained ReactionTransitionEvents is the pattern that actually exists in-codebase (Reaction_03006, Reaction_03007).
