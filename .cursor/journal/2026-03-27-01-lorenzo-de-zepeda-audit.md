# Lorenzo de Zepeda (_01090) Audit

## Card Text
- **Reaction:** When you announce an ability • Resolve it as if you are not the first player.
- **Technique:** Reveal and replace the top card of the adversary's deck. When their next round begins, they may play it as their combat card by discarding a card. If they do not, wound them.

## Files Audited
- `modules/php/cards/_7s5s/_01090.php` (main class)
- `modules/php/cards/_7s5s/actions/Action_01090.php` (pre-activation action)
- `modules/php/cards/_7s5s/reactions/Reaction_01090.php` (reaction)
- `modules/php/cards/_7s5s/techniques/Technique_01090.php` (technique)
- `modules/php/States/_7s5s/State_duelNewRound_01090.php`
- `modules/php/States/_7s5s/State_duelChooseTechnique_01090.php`

## Bug Found & Fixed

**Technique_01090 line 47: Revealing from the wrong faction deck.**

The code was calling `$game->getCardsOnTopOfPlayerFactionDeck($owner->ControllerId, 1)` which reveals the top card of **Lorenzo's controller's** faction deck. The card text says "the adversary's deck" — so it should be the duel opponent's deck.

WHY the bug existed: The code determined the adversary *after* the deck lookup. The `$owner` (Lorenzo) was conveniently available first, and `$owner->ControllerId` was used when `$adversary->ControllerId` was intended. Classic variable-ordering mistake.

Fix: Reordered the code so `$actor` and `$adversary` are resolved first, then the deck lookup uses `$adversary->ControllerId`.

## Everything Else Is Correct

- **Reaction:** Properly triggers on `EventActionTriggered`, correctly filters for `IAbilityThatDependsOnNotBeingFirstPlayer` (smart optimization — no point offering the reaction if the ability wouldn't benefit from it), sets the override global, cleans up on turn end.
- **Action:** Pre-activation mechanism for the reaction. Sets `OVERRIDE_AS_NOT_FIRST_PLAYER` and grants an extra action so the player can commit to the reaction before announcing an ability. This is a UX/game-flow necessity since you can't interrupt your own ability announcement with a reaction in this framework.
- **Technique (rest of it):** `EventDuelNewRound` handler correctly triggers when the adversary's round starts. Wound path correctly wounds the actor (adversary). Play-as-combat-card path correctly discards from hand, sets CHOSEN_CARD, announces combat card.
- **States:** Both `DUEL_CHOOSE_TECHNIQUE_01090` (multipleactiveplayer, acknowledge reveal) and `DUEL_NEW_ROUND_01090` (activeplayer, choose discard-to-play or take wound) are correctly defined.
