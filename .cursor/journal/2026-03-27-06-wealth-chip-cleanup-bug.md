# Wealth Chip Not Removed on Back Button

## The Bug

When in the `playerPayForReaction` state, pressing the Back button left the wealth cost chip visually stuck on the reaction card in the faction hand. The chip should be removed when leaving the state.

## Root Cause

The `onLeavingState` handler for `playerPayForReaction` (and several other pay-for-card states) iterates over hand cards and only destroys the `_wealth_cost` element if the card has the `_7sfs-unselectable` CSS class. But the `onEnteringState` handler never added that class to the card.

The bga-cards library's `setSelectableCards()` method was being used to mark the card as non-selectable, but it applies the library's own default class (`bga-cards_disabled-card`), NOT the game's custom `_7sfs-unselectable` class. So the cleanup check in `onLeavingState` never found the class and never destroyed the chip.

## WHY This Pattern Exists

The `_7sfs-unselectable` class serves a dual purpose:
1. Visual: it prevents the pointer cursor and click interaction (see CSS rules in `seventhseacityoffivesails.css`)
2. Cleanup marker: the leaving state uses it to identify which cards have wealth chips that need destroying

Other states that properly implemented this (like `highDramaPhase01113_3` in `OnEnteringState.7s5s.js` line 1479, and `duelPayForManeuverFromCombatCard` line 662) explicitly added `_7sfs-unselectable` before placing the wealth chip. The pay-for-card states in `OnEnteringState.js` were all missing this.

## Fix Applied

Added `dojo.addClass(cardElement, '_7sfs-unselectable')` in the `onEnteringState` for all four affected states:
- `playerPayForReaction` (the reported bug)
- `highDramaEquipActionPayForAttachmentFromHand`
- `highDramaInHandActionPay`
- `highDramaBruteActionPayForBrute`

All four had the same latent bug and all four have Back buttons. The fix matches the pattern used by states that were already working correctly.

## File Modified

- `modules/js/OnEnteringState.js`
