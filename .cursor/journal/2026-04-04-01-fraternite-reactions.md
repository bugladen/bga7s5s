# Fraternité! (_02030) Reaction Implementation

## What Was Done

Implemented both reactions on the Fraternité! Risk card (`_02030`). The card has two reactions with identical effects but different triggers:

1. **Reaction A** (`Reaction_02030a`): Triggers when an ability is announced — covers `EventActionActivated`, `EventReactionActivated`, `EventTechniqueActivated`, `EventManeuverActivated`.
2. **Reaction B** (`Reaction_02030b`): Triggers when a challenge is issued — covers `EventChallengeIssued`.

Both reactions grant the Musketeer trait to a player-chosen character until the action resolves (`EventActionResolved`).

## Key Design Decisions

### WHY: RiskReaction, not CardReaction
The card is a Risk played from hand, so both reactions extend `RiskReaction`. This gives the correct description prefix ("Faction Hand > CardName > Reaction:") and plugs into the framework's pay-from-hand flow.

### WHY: Pay state flow even with WealthCost = 0
Initially considered skipping the pay state since WealthCost is 0, but every RiskReaction in the codebase goes through the full pay flow (`EventEnteringPayState` + `EventReactionPayTransitionEvent`). The framework handler at `FrameworkActionsTrait.php:1895-1937` uses the pay state to:
- Discard the Risk card from hand (marking it as "played")
- Fire `EventRiskPlayed`
- Fire `EventRiskReactionTriggered`

This was confirmed by `Reaction_01080` (Friends at Court) which also has WealthCost = 0 but uses the full pay flow. Skipping the pay state would break the card lifecycle.

### WHY: Three-phase handleEvent
The `handleEvent` method has three distinct responsibilities:
1. **Trigger phase**: Detect the game event, gate on `LOCATION_HAND` and `isAvailable()`, queue `ReactionTransitionEvent`.
2. **Post-payment phase**: On `EventRiskReactionTriggered`, apply the Musketeer trait. This runs AFTER the framework has handled payment and discarded the card from hand.
3. **Cleanup phase**: On `EventActionResolved`, remove the Musketeer trait.

The actual effect (addTrait) happens in the `EventRiskReactionTriggered` handler, NOT in `performReaction`. The `performReaction` only stores the player's choice and initiates the pay flow. This is the standard pattern — see `Reaction_01080`, `Reaction_01023`, `Reaction_02019`.

### WHY: Separate classes for each reaction (a/b suffix)
Each "Reaction:" block on the card maps to a separate class. They share identical effect logic but have different trigger events. The a/b suffix convention follows existing patterns (`Reaction_01116b`, `Reaction_01146b`). Considered a shared base class but the code is straightforward enough that the duplication is minimal and keeps each reaction self-contained.

### WHY: getCharactersInCityByPlayerId for character selection
The card says "Your character gains Musketeer" — the player chooses which of their characters in the city gets the trait. Used `Theah::getCharactersInCityByPlayerId()` which returns all characters a player controls that are currently in city locations (not at home).

### WHY: Skip EventReactionActivated from own card (self-trigger bug fix)
When the player clicks a character button, `ReactionTrait::reactionFromCard` queues an `EventReactionActivated` event BEFORE `performReaction` runs. At that point the card is still in hand and `isAvailable()` is still true (setUsed hasn't been called — that happens later in the EventRiskReactionTriggered handler). So Reaction_02030a catches its own activation as "an ability being announced" and queues another ReactionTransitionEvent, creating an infinite loop.

Fix: in the trigger check, skip `EventReactionActivated` events where `$event->sourceId == $owner->Id`. This also prevents cross-triggering between Reaction_02030a and Reaction_02030b (same card, same sourceId). Once one reaction fires and goes through the pay state, the card is discarded from hand so the other reaction can't fire anyway.

## Observations

- The Musketeer trait matters for cards that check `hasTrait("Musketeer")` — it affects valid performers/targets for certain abilities.
- Per the card's reminder text, Reaction A fires before performers/targets are chosen (during announcement), which means the Musketeer trait can influence the subsequent performer/target selection.
- For Reaction B, the subsequent duel is part of the challenge action, so the Musketeer trait persists through the entire duel until `EventActionResolved`.
- Null checks on character lookup in cleanup are defensive — the character could theoretically be removed from play before the action resolves.
