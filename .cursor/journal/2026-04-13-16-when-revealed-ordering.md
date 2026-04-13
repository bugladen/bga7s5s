# When Revealed Effect Ordering Implementation

## What Was Done
Implemented the full "When Revealed" effect ordering system. When multiple cards have When Revealed effects, the First Player now chooses the resolution order via buttons.

## Architecture Decisions

### WHY separate event loops (240 vs 245)?
The choice state (243) gets its own events/reactions/pay cycle (245/246/247) rather than reusing 240/241/242. This is because:
- State 240 is the initial event loop for the 0-or-1 card path
- State 245 is the loop for each individual card chosen by the First Player
- Both feed into the checkRemaining state (244) via endOfEvents
- Keeping them separate follows the existing pattern where every activeplayer state group has its own event/reaction sub-states

### WHY a global for remaining cards?
Used `WHEN_REVEALED_REMAINING_CARDS` global (JSON array) to track which cards still need their When Revealed effect fired. BGA framework is stateless between requests, so this must persist in the database. Cards are removed from the list as they're chosen, preventing double-firing.

### WHY not re-scan hasWhenRevealedEffect on each loop?
Explicit tracking by removal is more reliable. A When Revealed effect might not change any detectable state, and re-scanning could miss that it was already processed.

### WHY EventCardWhenRevealedEffect instead of reusing EventSchemeCardRevealed?
EventSchemeCardRevealed fires during stPlanningPhaseApproachCardsPlayed for ALL schemes (triggering panache modification in Leader.php, Crystal Eye reactions, etc.). EventCardWhenRevealedEffect is a separate event specifically for the When Revealed resolution phase. This keeps the two concerns separated — scheme reveal notifications vs when-revealed effect execution.

## State Flow
- State 24 → [0 cards] → 240 → 244 → MUSTER
- State 24 → [1 card] → auto-fire → 240 → 244 → MUSTER
- State 24 → [>1 cards] → 243 (choice) → 245 (events) → 244 (check) → loop or MUSTER

## Files Changed
- `modules/php/States.php` — 5 new state constants (243-247)
- `modules/php/Game.php` — WHEN_REVEALED_REMAINING_CARDS global
- `modules/php/theah/Events.php` — CardWhenRevealedEffect event constant
- `modules/php/EventFactory.php` — createCardWhenRevealedEffectEvent factory method
- `modules/php/StatesTrait.php` — Rewrote stPlanningPhaseResolveWhenRevealedCards, added stPlanningPhaseResolveWhenRevealedCardsCheckRemaining
- `states.inc.php` — Modified state 24 and 240 transitions, added states 243-247 and 244
- `modules/php/ArgumentsTrait.php` — argsPlanningPhaseResolveWhenRevealedCardsChooseOrder
- `modules/php/FrameworkActionsTrait.php` — actChooseWhenRevealedCard
- `modules/php/cards/_7s5s/_01151.php` — Switched from EventSchemeCardRevealed to EventCardWhenRevealedEffect
- `modules/js/OnUpdateActionButtons.7s5s.js` — Button handler for choice state

## Untested
No test runner available. Needs manual testing on BGA Studio with:
1. No when-revealed cards (most games) — should flow through silently
2. Single when-revealed card (just _01151) — auto-fires
3. Multiple when-revealed cards — First Player gets choice buttons
