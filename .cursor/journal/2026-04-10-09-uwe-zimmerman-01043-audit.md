# Uwe Zimmerman (01043) Audit

## Card Text
**Passive:** While using your abilities, Uwe may be considered a Mercenary. (For costs and effects.)
**Passive:** While the adversary is a Sorcerer, Uwe's combat cards gain +1 [Thrust].

## Mercenary Trait — Correct (dead code removed)

### hasTrait override is the right mechanism
The `hasTrait` method overrides the Mercenary check: when a card passes itself as `$queryCard` and is one of `_01036`, `_01039`, or `_01051`, Uwe is considered a Mercenary. This covers all 3 Eisen synergy cards that query for Mercenaries via `$queryCard`. Callers already validate ControllerId, so "your abilities" scoping is handled upstream.

### WHY reactions can't work here
The Mercenary check runs during `isAvailableToPlayer` / `getPerformersForAction` — the card selection phase. This happens before events fire. Reactions can only respond to events, so a reaction can't pre-empt a trait check during selection. The hasTrait override is the only mechanism that works.

### Dead code removed
- **Reaction_01043**: handleEvent was empty (never triggered), no performReaction override (would have caused a softlock if somehow triggered). Showed perpetually as "available" in the UI despite doing nothing. Deleted the file and removed IHasReactions/ReactionTrait from _01043.
- **EventPlayerTurnEnd handler**: Removed Mercenary from ModifiedTraits, but nothing ever added it. Was meant for the abandoned reaction approach.

### The "may" question
Card says "may" — this is implicit in the current implementation. The player chooses to use their ability (selecting Uwe as performer, target, etc.), which is the opt-in. For Technique_01039, having Uwe qualify as Mercenary auto-enables the technique, but this is always beneficial.

## +1 Thrust vs Sorcerer — Correct
`handleEvent` on `EventDuelCalculateCombatCardStats` checks `$event->actorId == $this->Id` + adversary has Sorcerer trait. Adds +1 Thrust. Straightforward, no issues.

## Items verified
- hasTrait override covers _01036, _01039, _01051 ✓
- No other cards pass $queryCard when checking Mercenary ✓
- Ownership handled by callers (ControllerId filter) ✓
- +1 Thrust on combat cards vs Sorcerer ✓
- Dead Reaction_01043 removed ✓
- Dead EventPlayerTurnEnd cleanup removed ✓
- No linter errors ✓
