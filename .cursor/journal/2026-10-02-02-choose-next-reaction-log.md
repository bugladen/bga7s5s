# chooseNextReaction — public available-reactions log

## Ask
When First Player hits `chooseNextReaction`, write a game log listing every
available reaction so opponents can see what's on the table (they only get the
status-bar description otherwise).

## Approach
- Extracted `buildChooseNextReactionChoices()` from `argsChooseNextReaction`.
- New `notifyAvailableReactionsForChooseNext()` posts
  `The First Player is choosing the order of reactions:<br>${reactions_list}`
  using those same labels.
- Call site: `Theah::runEvents` divert, right before `nextState('chooseNext')`.
- Log lines are `&bull; `-prefixed so long reaction labels stay scannable.

## WHY
- **Buttons vs log Risk labels differ on purpose.** Buttons: FP still sees their
  own Risk card names so they can order multiples; opponent Risks stay
  `{Player} - Risk Reaction`. Public log: **every** Risk is fogged
  (`fogAllRiskNames: true`) so FP's hand Risk names never leak to opponents.
- **Log at divert, not in args.** `argsChooseNextReaction` re-runs on client
  refresh / reconnect and would spam duplicate log lines.
- Re-enters after each pick while ≥2 remain — each divert logs the *current*
  remaining set. Intentional; the table changes as reactions resolve.

## Prior context
Reaction-order choose itself: journal `2026-09-30-02-reaction-order-choose-impl.md`.
This session also had Vittoria/Dabney challenge targeting work
(`2026-10-02-01`) — unrelated to this log, just same day.
