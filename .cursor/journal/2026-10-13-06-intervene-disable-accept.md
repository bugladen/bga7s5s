# Accept Challenge: disable Accept when intervener selected

## Context
In `highDramaChallengeActionAcceptChallenge`, selecting a character enables the Intervene button via shared `onCardInPlayClicked`. Accept stayed clickable next to it — easy to mis-click Accept (original defender) instead of Intervene.

## Change
`EventHandlers.js` `onCardInPlayClicked`: when state is accept-challenge and `selectedCards.length > 0`, add `disabled` to `btnAccept`; clear it when selection empties. Intervene toggle unchanged.

## WHY state-gated here
Confirm enable/disable is shared across many states. Only this state has Accept + Intervene side by side. Gating on `gamestate.name` keeps other pickers untouched. Deselect re-enables Accept so the player can still accept without intervening.
