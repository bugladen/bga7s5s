# Silver Spine + Censure — challenge cancel no-op

## Report

Opponent played Censure (`_03057`) targeting a character with Silver Spine
(`_03cd21`). Chat showed Spine's cancel notify ("cancels the effects of the
opposing Risk") and the once-per-Day chip fired, but Accept/Refuse still
appeared and the challenge proceeded.

## Rules answer

Yes — Silver Spine **should** cancel challenge actions from opponent Risks.
Printed text is "the first time an opponent's Risk targets the equipped
character • Cancel the effects." Censure is a Risk that implements
`IRiskThatTargetsCharacters` and issues a challenge; `EventChallengeIssued`
is one of the five interception points (Maryam pattern).

## Root cause

Spine's `EventChallengeIssued` branch did fire:
- `isOpponentRiskTargetingCharacters` passed (Censure + opponent controller)
- `markAbilityUsed` + notify ran (matches chat)
- `$event->canceled = true` ran → EventHub skips DUEL_CHALLENGER/DEFENDER

But the challenge state machine does **not** look at `$event->canceled`.
`stChallengeActionCheckCancelled` only aborts on `Game::CHALLENGE_CANCELLED`.
Every player Reaction that cancels a challenge (01088, 01032, 01014, 01053,
03031) sets that global. Forced cancels on Spine/Maryam did not.

Flow for Censure:
1. `stIssueChallenge` queues `EventChallengeIssued` (sourceId = Risk)
2. SETUP_CHALLENGE_EVENTS runs it → Spine cancels + notifies
3. … → CHECK_CANCELLED sees `CHALLENGE_CANCELLED=false` → `notCancelled`
4. Accept UI still offered

## Fix

On `EventChallengeIssued` cancel in `_03cd21` and `_01186`, also
`globals->set(Game::CHALLENGE_CANCELLED, true)`.

Maryam had a special-case in CHECK_CANCELLED only when she is the
*challenger* vs Defending Honor — not when she is the defender. Same
global gap for defender-path Risk challenges.

## Not this bug

- Unused `EventCharacterTargeted` import on Spine (handler claimed in
  2026-05-18 journal but absent). Irrelevant to Censure (challenge path).
- Missing `deleteEventBatch` on Spine vs Maryam — Censure's challenge
  event isn't batched with follow-ups that would need deletion.

## Unfinished

Verify in play: Spine equipped, opponent Censure → cancel message +
"Challenge was cancelled" + no Accept UI + ability spent for the Day.
