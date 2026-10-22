# Same-player hand reactions skip First Player choose

User correction after the split-button attempt: keep combining one player's hand reactions into a single button. The actual bug is that this set still opened chooseNext, so First Player stared at one button.

## Reverted

`buildChooseNextReactionChoices` is back to the 2026-09-30 combiner. Non-FP RiskReactions from one player are one `{Player} - Risk Reaction` button and one click marks every one of those transitions `runImmediately`. FP's own risks stay separate named buttons. Do not split them again so FP can "pick which hidden card" — they cannot see the names, and the user wants the batch.

## New: auto-release when the whole tier is one hand

`Theah::runEvents` diverts when the current priority tier has ≥2 reaction transitions. If those are all in one player's hand, the combiner would show a single button and there is nothing to order against.

`Theah::releaseHandReactionsFromOnePlayer` checks the tier already fetched (same player, every source card `Location === hand`, not a Theah-framework reaction). On match it sets `runImmediately` on each and `runEvents` `continue`s. `getNextEventByRunImmediately` then drains them in queue order. No choose log, no active-player change.

WHY on Theah, not ArgumentsTrait: the user pulled it out of the args builder. The decision runs inside `runEvents` before choose-next args exist, next to `setEventRunImmediately` / `getQueuedReactionTransitionEvents`. ArgumentsTrait stays labels and the public log.

WHY flags instead of just skipping the divert: a reaction transition `nextState`s out of `runEvents` after the first one. The rest must stay promoted or the next pass sees count > 1 and asks again. Same mechanism as the shared button. No defer — this tier has no other reactions to push aside.

WHY location, not `instanceof RiskReaction`: the user said in-hand. A board reaction from that same player, or two different players' hands, still goes to chooseNext. Opponent risks that are not in hand still share a button when mixed with something else; they do not auto-release.

## Tests

Basics suite calls `$game->theah->releaseHandReactionsFromOnePlayer`. Full `php tests/run.php` after the move: 5574 passed.
