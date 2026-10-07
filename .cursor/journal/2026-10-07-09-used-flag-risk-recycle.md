# Used flag: Risk play + discard recycle

## Answers (investigation)

1. **Risk play does NOT set Used on all attached abilities.** Only the ability being played gets Used=true.
   - Action: `actPayForInHandAction` calls `$action->setUsed(true)` after parking risk in Purgatory, before queueing discard-as-played. Siblings untouched.
   - Reaction: `actPayForReaction` never calls setUsed. Individual RiskReactions call setUsed when they resolve (performReaction / EventRiskReactionTriggered), often after discard is already queued/applied. Again only that reaction.

2. **Used clears primarily on EventDuskEndOfDay** for CardAction/CardReaction (`setUsed(false)`). Maneuver/Technique default to DuelEnd (`ResetOnDuelEnd`), optional Dusk if `ResetOnDayEnd`. Also ad-hoc card-specific clears. `resetCard()` does NOT touch Used.

3. **Recycle does NOT clear Used.** `shufflePlayerDiscardIntoPlayerFactionDeck` only moveCard discard→faction + shuffle. No setUsed/reset. Used lives on serialized ability objects on the card. Dusk later clears Actions/Reactions while card is in discard (buildCity loads Discard-*) or in faction deck (`deliverDuskEndOfDayToFactionDecks` special pass — faction decks omitted from buildCity).

## WHY this matters
If something checks isAvailable() on a sibling ability of a played Risk, or redraws a Risk same-day before dusk without going through dusk clear, Used can still be true. Recycle itself is not a reset path.
