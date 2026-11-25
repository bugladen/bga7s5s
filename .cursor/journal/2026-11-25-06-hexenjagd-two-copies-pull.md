# Hexenjagd two copies — cancel then decline lets Pull resolve

## Report
Two Hexenjagd in hand. Opponent plays Pull. Player cancels with one (pays), prompted for the second, Declines second → Pull still resolved.

## Root cause (two cooperating bugs)

1. **Sibling offer not cleared.** Hexenjagd offers on `EventSorcererAbilityStart` without intercepting (unlike UL). Both hand copies stack reaction transitions; `releaseHandReactionsFromOnePlayer` drains them in queue order. First cancel sets `holdingEffects` but left the sibling transition live → second prompt mid hold/pay. Mireli (`Reaction_01135`) already clears sibling transitions on cancel; Hexenjagd did not.

2. **`REACTION_ID` pollution.** `actPayForReaction` reads `Game::REACTION_ID`. Sibling Decline overwrites that global to `"decline"` before this copy's pay stacks `EventRiskReactionTriggered`. Hexenjagd then saw `reactionId == 'decline'` and **skipped** cancel cleanup (no `clearEvents` / cost wound / `setUsed`). Separately, Decline-after-Back on the *holding* copy intentionally `releaseEvents`s — that is the path that re-queues Pull. Double prompt made the failure mode easy to hit / easy to misread as "the other copy."

## Fix
1. `clearSiblingHexenjagdReactionTransitions` on cancel choice (`performReaction`) and again on successful `EventRiskReactionTriggered` (Mireli shape).
2. Sticky `chosenCancelReactionId` — if Triggered arrives with polluted `decline`/`pass` while still holding, use the sticky cancel-* id and finish cleanup.
3. Decline only `releaseEvents` / `skipNextEvent` when `holdingEffects` (never-engaged sibling is a pure no-op).

## Tests
`Reaction_01053`: sibling clear on cancel; Triggered with `reactionId=decline` still cancels via sticky choice; never-engaged Decline releases nothing. 19/19 green.

## WHY not refactor to UL intercept-at-offer
Would stop double offers structurally, but is a larger behavioral change for every Sorcerer cancel. Sibling clear + sticky id matches an existing proven pattern (01135) and fixes the reported failure without retiming hold/pay.
