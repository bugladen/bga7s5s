# Silver Spine false-cancel on Celerity

## Report
Player used Celerity (`_04047`) targeting Odette with Silver Spine (`_03cd21`). Log showed Spine's cancel notify ("cancels the effects of the opposing Risk"). Challenge still proceeded. User: challenge continuing is correct; the cancel announce is the bug.

## Rules
Celerity prints "These effects cannot be cancelled." and returns `effectsCannotBeCancelled() === true`. Night of Drinking already gates on that. Silver Spine Forced cancel did not — so it announced and spent the Day while the printed immunity left the challenge standing.

## Why the challenge still went through (even without the immunity gate)
Framework challenge target-pick queues `EventCharacterTargeted` then `endOfEvents` → `TECHNIQUE_AVAILABLE`. Spine's CharacterTargeted path announced + spent + `$event->canceled`, but did **not** set `CHALLENGE_CANCELLED`. `stTechniqueAvailable` only aborts on that flag (UL / Blood Like Winter lesson). Ability already spent → later `EventChallengeIssued` skipped. So: false cancel log + duel still issues. Same hole for cancellable Risk challenges (Censure) after CharacterTargeted was added for Defending Honor.

## Fix
1. Spine `isOpponentRiskTargetingCharacters`: also require `!effectsCannotBeCancelled()`.
2. Maryam: same gate via `isCancellableRiskTargetingCharacters` (would have the same false announce).
3. CharacterTargeted cancel: when `CHALLENGE_TYPE` is a framework Risk challenge (not NORMAL, not DEFENDING_HONOR), set `CHALLENGE_CANCELLED` and let `skipChallengeBeforeTechnique` resolve. Defending Honor stays on ActionResolved (outside that hub).

WHY not gate only on 4511 state id: Forced handlers have no `$state` arg; FakeGamestate has no `state_id()`. Challenge type is already set before the picker for Censure/Celerity/etc. NORMAL left alone so non-challenge targeting Risks do not poison the next basic challenge.

## Tests
`Card_03cd21_Test`: Celerity CharacterTargeted + ChallengeIssued do not cancel/spend; Censure target-pick sets CHALLENGE_CANCELLED without premature ActionResolved. `Card_01186_Test`: Celerity does not spend Imperviousness. 22/22.

## Unfinished
None for this report. Rage (`_05Hulk`) / Unsanctioned (`_02061`) share the same hook and are covered by the method gate.
