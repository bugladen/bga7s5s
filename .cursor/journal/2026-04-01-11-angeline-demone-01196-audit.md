# Angeline Dèmone (_01196) Audit

## Card Text
> Negotiable (You may parley when paying for this card.)
> When Angeline would engage from moving, you may discard a card instead of engaging her.
> Technique: If Angeline has equal or greater [combat] and [inf] than the adversary • +1 Riposte

City Character. Combat=3, Finesse=2, Influence=2, Resolve=4. WealthCost=5. Traits: Mercenary, Pirate, Sorcerer, Montaigne.

## Bug Found and Fixed

**Technique condition checked SUM instead of individual stats.**

Card says "equal or greater [combat] AND [inf] than the adversary" — meaning both stats individually must be >=. Code was checking:
```php
$actor->ModifiedCombat + $actor->ModifiedInfluence >= $adversary->ModifiedCombat + $adversary->ModifiedInfluence
```

This lets the technique activate when one stat compensates for the other. E.g. Combat=5/Influence=1 vs Combat=2/Influence=3: sum 6>=5 passes but Influence 1<3 should fail.

Fixed all three occurrences (isAvailableToPlayer, EventDuelCalculateTechniqueValues handler, EventGenerateChallengeThreat handler) to:
```php
$actor->ModifiedCombat >= $adversary->ModifiedCombat && $actor->ModifiedInfluence >= $adversary->ModifiedInfluence
```

Also fixed explanation messages from "has more Combat and Influence" to "has equal or greater Combat and Influence" to match card text.

## Verified Correct

**Negotiable**: `$this->Negotiable = true` set in constructor. Standard property handling.

**Reaction (engage from moving)**: Triggers on `EventCardMoved` when `$event->engage && !$angeline->Engaged`. Key insight: `EventCardMoved` has `runEventHubAfterCards = true`, so the card handlers fire BEFORE the EventHub sets `Engaged = true`. The reaction queues a `ReactionTransitionEvent`, pausing to ask the player. If the player discards, an engarde event is queued to set `Engaged = false`, undoing the engagement that the EventHub applied. If the player passes, Angeline stays engaged. Correct either way.

**No setUsed on the reaction**: Intentionally correct. The card text has no "once per day" limitation — it's a continuous ability with a natural cost gate (must have cards in hand to discard). Contrast with Reaction_01116a which explicitly calls setUsed after use.

**Button generation**: Lists all cards in active player's hand plus a Pass button. The active player is set by the ReactionTransitionEvent to Angeline's controller.
