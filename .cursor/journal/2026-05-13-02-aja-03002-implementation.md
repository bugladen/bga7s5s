# Aja (`_03002`) — Vicious and Useful

Aja is a Character (not Leader, not CityCharacter) in the faf expansion.
Vodacce / Mbey / Assassin / Duelist / Spy. Cesca del Rosso's crew.

## Card text

- **City Action:** Engage Aja • Issue a **Combat** challenge to target
  opposing character. Only characters with 3 **Finesse** or more may
  intervene or refuse the challenge.
- **Gambling Technique:** If the adversary is wounded • Gain **Lethal**.

The user clarified "Gambling Technique" = a technique available only if
the actor has gambled for their combat card.

## City Action — `Action_03002`

Single-step CharacterAction; the cost (engage Aja) is paid automatically
by the framework's `stIssueChallenge` once we add the new challenge type
to its auto-engage list. Pattern mirrors `Action_02013` (Wilhelm Dünst):

1. State picks target opposing character at Aja's location.
2. On selection, set `CHOSEN_PERFORMER = Aja`, `CHOSEN_TARGET`,
   `CHALLENGE_STAT = STAT_COMBAT`, `CHALLENGE_TYPE = AJA_CHALLENGE_TYPE`.
3. Queue `createTransitionEvent("03002_2")` → maps to
   `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` in `states.inc.php`.
4. Call `nextState()` (empty) → state transitions back to
   `HIGH_DRAMA_PLAYER_TURN_EVENTS` so queued events flush.

**WHY Wilhelm's pattern (createTransitionEvent → flush) rather than
Cesca's pattern (direct `nextState("transitionName")`):** Cesca's two-step
action stays within the high-drama player turn states, so direct state
transitions work. Aja is jumping out of player-turn states into the
challenge sub-state machine; the events queue (engage event from
`stIssueChallenge`) needs to flush, and the canonical place to do that is
the EVENTS dispatch state. Every existing "issue a challenge" action
(`02013`, `02034`, `01083`) routes through events.

**No `createActionResolvedEvent` in the action.** The challenge
resolution flow fires it (cancelled path in `stChallengeActionCheckCancelled`,
or the threat-resolution path). Comment in the action documents this.

**`IAbilityThatTargetsCharacters`.** Aja's challenge targets a character.
Lets "before being targeted" hooks fire correctly.

## Gambling Technique — `Technique_03002`

`isAvailableToPlayer` gates on:

1. In a duel (`Game::IN_DUEL`).
2. Actor has gambled this round (`Game::DUEL_GAMBLED`). This is the
   "Gambling Technique" semantic — only available if the actor gambled
   for their combat card.
3. The owner (Aja) is the current round actor — `getDuelRoundActor()`.
4. The adversary is wounded (card-text precondition) —
   `getDuelRoundOpponent()->Wounds > 0`.

If all pass, technique is offered. On activation:

- `EventDuelCalculateTechniqueValues` fires with `techniqueId == this->Id`.
- Queue `createGainLethalEvent($event->actorId, $event->theah)`.

**WHY `EventDuelCalculateTechniqueValues` and not
`EventGenerateChallengeThreat`:** The latter fires only at City Action
challenge resolution (single-roll combat, no duel). Gambling is
exclusively an in-duel mechanic, so `Technique_GainLethal` (the generic
helper) handling both events doesn't apply here — Aja's technique can
only fire during a duel. Mirrors `Technique_01049` (also Gain Lethal,
also duel-only) which uses the same event.

**`Game::DUEL_GAMBLED` lifecycle:** Set true in
`FrameworkActionsTrait::actChooseGambleCard` (line 1568) once the gambled
combat card is locked in. Cleared by `stDoneRound` (StatesTrait line
1514). So the flag is reliable for "this actor has gambled this round."

The alternative was querying `duel_round.gambled` via SQL each time
`isAvailableToPlayer` runs — that would be a per-call DB hit on a
hot path. The global is cheaper and equivalent.

## New challenge type — `AJA_CHALLENGE_TYPE = 18`

Three integration points:

1. **Auto-engage on issue.** Added to the list in
   `StatesTrait::stIssueChallenge` (`NORMAL | SERVO_SCARPA | TORVO_ESPADA
   | AJA_VICIOUS_USEFUL`). That's how "Engage Aja" cost is paid.

2. **Intervention filter.** Two layers:
   - Server: `ArgumentsTrait::argsHighDramaChallengeActionAcceptChallenge`
     post-filters `$charactersCanIntervene` by `ModifiedFinesse >= 3`
     when the challenge type is AJA.
   - Server: `Theah::interventionCheck` throws if AJA and
     `$character->ModifiedFinesse < 3` (defense-in-depth — client could
     skip the filter, server must still enforce).

3. **Refusal restriction.** "Only characters with 3 Finesse or more may
   intervene or refuse the challenge" — I read this as: the *defender at
   the time of refusal* must have Finesse ≥ 3. If the printed target's
   Finesse < 3, they cannot refuse — they must accept or have someone
   else (with ≥3 Finesse) intervene.
   - Server: `FrameworkActionsTrait::actHighDramaChallengeActionReject`
     throws if AJA and `target->ModifiedFinesse < 3`.
   - Client: `OnUpdateActionButtons.js`
     (`highDramaChallengeActionAcceptChallenge`) disables `btnRefuse` if
     AJA and `args.defenderFinesse < 3`.
   - I added `defenderFinesse` to the args output so the client can
     check without an extra round-trip.

## State

- `HIGH_DRAMA_PLAYER_TURN_03002 = 403002` (simple `4` + cardId per the
  state-id-encoding feedback memory).
- `State_highDramaPhase03002` — active player picks one opposing target
  at Aja's location. Transitions `""` and `"zombie"` both go to
  `HIGH_DRAMA_PLAYER_TURN_EVENTS` so the challenge transition event can
  fire.
- `states.inc.php` mappings: `"03002" => HIGH_DRAMA_PLAYER_TURN_03002`
  (entered from EventActionTriggered), `"03002_2" =>
  HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` (entered after target
  is chosen).

## Faction

`initializeFaction("Vodacce")`. Cesca del Rosso (the Leader for this
deck) is Vodacce / Red Hand / Strega Sorcerer; Aja shares the Mbey crew
trait but the deck's faction identity is Vodacce.

## Files touched

- `modules/php/cards/faf/_03002.php` — wired interfaces, traits,
  Actions/Techniques arrays, faction init.
- `modules/php/cards/faf/actions/Action_03002.php` — new.
- `modules/php/cards/faf/techniques/Technique_03002.php` — new
  (`modules/php/cards/faf/techniques/` directory is new for the
  expansion).
- `modules/php/States/faf/State_highDramaPhase03002.php` — new.
- `modules/php/States.php` — new constant.
- `modules/php/Game.php` — new challenge type constant.
- `seventhseacityoffivesails.js` — new client-side challenge type
  constant.
- `states.inc.php` — two transition mappings (`"03002"` and `"03002_2"`).
  `"03002_2"` is justified here (unlike `"03001_2"` which was dead) —
  the action queues `createTransitionEvent("03002_2")`.
- `modules/php/StatesTrait.php` — added AJA to the engage-on-issue list.
- `modules/php/theah/Theah.php` — added AJA filter to `interventionCheck`.
- `modules/php/ArgumentsTrait.php` — filtered interveners by Finesse ≥3
  for AJA challenge type; surfaced `defenderFinesse` to client.
- `modules/php/FrameworkActionsTrait.php` — added AJA restriction to
  `actHighDramaChallengeActionReject`.
- `modules/js/OnUpdateActionButtons.js` — disable `btnRefuse` for AJA
  challenge type when defender Finesse <3.
- `modules/js/OnEnteringState.faf.js`,
  `modules/js/OnUpdateActionButtons.faf.js`,
  `modules/js/OnLeavingState.faf.js` — `highDramaPhase03002` handlers.

## Pre-commit hook compliance

- `Action_03002 extends CharacterAction` — hook's regex doesn't match
  `CharacterAction` directly, so no `createActionResolvedEvent`
  requirement triggers. Per CLAUDE.md the convention is to call it
  from CharacterAction subclasses, but here the challenge resolution
  flow fires it (matches `Action_01083`'s comment-only justification).
- No `setUsed` / `resetPlayerPassCount` / `announceAction` in the action.
- No `ISorcererAbility`.
- No `IAbilityThatTargetsCards` on either ability class.

## Open questions / risks

- **Refusal restriction interpretation.** "Only characters with 3
  Finesse or more may intervene or refuse the challenge" — I read this
  as the defender (at refusal time) needing ≥3 Finesse. Another
  reading: only the original target can refuse, and they need ≥3
  Finesse. Practically equivalent in the current flow, because the
  defender at refusal time IS either the printed target or an
  intervener who already passed the ≥3 Finesse gate. So either reading
  produces the same outcome. Flagging in case QA disagrees on a corner
  case where the defender's Finesse drops mid-challenge.

- **Adversary wound check at resolution.** `isAvailableToPlayer` checks
  "adversary is wounded" before listing the technique. The technique
  resolution path (`actDuelTechniqueChosen`) does NOT re-check
  availability — it trusts the client to only pick listed techniques.
  Defensive re-check could be added in `handleEvent` on
  `EventDuelCalculateTechniqueValues`, but no existing technique does
  this and adding it would diverge from the codebase pattern. Skipping
  for now.

- **Aja self-target via intervention.** Aja is the challenger, so she
  can't be a defender. The intervention filter excludes Aja
  automatically via `$character->Id != $targetId` and the controller
  check earlier in `argsHighDramaChallengeActionAcceptChallenge`. No
  extra guard needed.

- **Multiple challenges in same round.** `DUEL_GAMBLED` is per-round,
  cleared by `stDoneRound`. So Aja's technique can fire across
  multiple rounds if she gambles each round. Once-per-day or
  once-per-duel are not implied by the card text — "Gambling
  Technique" just gates availability on the gamble action, not on a
  use count. Default `Technique::ResetOnDuelEnd = true` clears the
  Used flag at duel end anyway. Confirmed by the printed text not
  containing any "once per X" language.
