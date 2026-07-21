# Defending Honor (_01078) — isValidTargetForAbility realignment

## The bug

`Action_01078::isValidTargetForAbility` was validating the **challenge target**
(the friendly character being challenged at the second step) instead of the
**ability's target** per the card text: "**Target enemy character** issues a
[Combat]challenge to one of your characters opposing them."

The old check used `CHOSEN_PERFORMER` (the enemy character, which IS the
ability target) and compared the incoming `$character` against it — i.e.,
treating the parameter as the friendly being challenged. 

## Why this matters

`isValidTargetForAbility` is consumed by reactions like Vittoria (Reaction_01014)
and Diplomatic Impunity (Reaction_02016) when they **redirect an ability's
target** to a different character. For Defending Honor, the redirect is on the
enemy character (the ability's actual target — corresponds to
`EventCharacterTargeted.targetId` per `2026-05-18-02-...`). So the method needs
to validate "could THIS character have been the original Defending-Honor
target?" — same logic as `getQualifiedCharacters`.

With the old code, Vittoria's redirect check was effectively a no-op (or
worse, validating against the wrong shape of character).

## What was changed

1. `Action_01078::isValidTargetForAbility` now validates the first-step target
   (enemy character) by mirroring `getQualifiedCharacters`:
   - controlled by an opponent of the Risk's controller
   - in the city
   - `canChallenge()`
   - has at least one friendly opposing them at the same location
   `$playerId` comes from `$this->getOwningCard($theah)->ControllerId` so the
   method works without relying on transient `CHOSEN_PERFORMER` globals
   (important: Vittoria calls this with a redirect candidate, not the original
   target, and may run before `CHOSEN_PERFORMER` is meaningful for this card).

2. `FrameworkActionsTrait::actHighDramaChallengeActionTargetChosen` now skips
   the `$action->isValidTargetForAbility(...)` call when
   `CHALLENGE_TYPE == DEFENDING_HONOR_CHALLENGE_TYPE`. The args function
   (`argsHighDramaChallengeActionChooseTarget`) already filters the legal
   choices (characters at performer's location, not controlled by active
   player), and the ability target for Defending Honor was chosen at the
   first step. Without this skip, the new validation logic would reject the
   friendly being chosen here (correctly! — it's not the ability target).

## What I considered and rejected

- **Branching `isValidTargetForAbility` on a global** to behave differently
  depending on step. Rejected: that's exactly the kind of "method does two
  different things" that caused this bug in the first place.

- **Adding an `IAbilityThatTargetsCharacters` instanceof check** in the
  framework call site. Doesn't help — `Action_01078` does implement it; the
  problem isn't that we shouldn't call the method at all, it's that for this
  specific challenge type the target was already validated at step 1.

- **Manually invoking `isValidTargetForAbility` inside Action_01078's
  performer-chosen path.** There isn't a clean per-action hook — performer
  selection runs through framework `actHighDramaInHandActionPerformerChosen`.
  `getPerformersForAction` already restricts the UI to qualified
  characters, so first-step validation is implicit. Adding a framework
  validation call there would be a broader, riskier change. If we later need
  an explicit re-validation (e.g. against state changes between
  list-generation and choice), the right place is the framework method, not
  per-action wiring.

## Follow-up: CHOSEN_PERFORMER stayed stale after Vittoria redirect

After the first round of changes, user reported: "I used Reaction_01014 to
redirect the target of Action_01078 to a thug. However, the framework still
thinks that _01014 is the target of Action_01078."

### Root cause

`Action_01078::handleEvent`, on `EventActionTriggered`, queues two events:
1. `EventCharacterTargeted` (priority 3 / MEDIUM) — fires reactions like
   Vittoria.
2. `EventTransition` (priority 8 / TRANSITION) — moves to the
   challenge-target-choosing state.

When Vittoria fires, she cancels the original `EventCharacterTargeted`, lets
the player pick a Thug, and re-queues a fresh `EventCharacterTargeted` with
`targetId = Thug` via `Reaction_01014::releaseEvent` (line ~493).

But `releaseEvent` only mutates the **event** — it does not touch
`Game::CHOSEN_PERFORMER`, which was set to Vittoria's id back in
`actHighDramaInHandActionPerformerChosen`. For other ability targets that's
fine (target ≠ performer for most cards). For Defending Honor specifically,
the ability target **is** the performer (the enemy character who issues the
challenge). So after a Vittoria redirect, the queue still had Vittoria as
`CHOSEN_PERFORMER`, and the downstream challenge state used Vittoria's
location to pick legal challenge targets.

### The fix

Added an `EventCharacterTargeted` branch to `Action_01078::handleEvent`. On
any uncancelled `EventCharacterTargeted` carrying this action's `abilityId`,
sync `CHOSEN_PERFORMER` to `event->targetId`. This works for both:

- the no-redirect path (the event fires once with `targetId = original
  performer`; setting CHOSEN_PERFORMER to the same value is a no-op), and
- the redirect path (the canceled original event is skipped because of
  `!$event->canceled`; the re-queued event with the new `targetId` fires and
  updates CHOSEN_PERFORMER before the lower-priority `EventTransition`
  reaches the challenge state).

### Why event ordering works out

Event priorities (lower number = higher priority):
- `EventCharacterTargeted` = MEDIUM_PRIORITY (3)
- `ReactionTransition` = REACTION_PRIORITY (6)
- `EventTransition` = TRANSITION_PRIORITY (8)

After Vittoria's redirect, the queue is roughly: re-queued
EventCharacterTargeted (3), pending ReactionTransition for `moveHome` step
(6), original EventTransition to challenge state (8). The targeted-event
handler thus runs strictly before the transition into the challenge state.
If we ever change `EventTransition` to a higher priority than
`EventCharacterTargeted`, this ordering breaks and the sync would need to
move (e.g. into `Reaction_01014::releaseEvent` with a per-ability hook).

### Why fix this in the Action, not the Reaction

`Reaction_01014::releaseEvent` already special-cases `challengeIssuedEvent`
to update `CHOSEN_PERFORMER` / `CHOSEN_TARGET`. Tempting to add the same for
`characterTargetedEvent`. Rejected: the reaction would have to encode
"target == performer" semantics that are specific to Defending Honor; any
future `IAbilityThatTargetsCharacters` ability that fires
`EventCharacterTargeted` where target ≠ performer would be broken by the
reaction silently rewriting CHOSEN_PERFORMER. Putting the sync on
`Action_01078` keeps that coupling where the semantic actually lives.

## Risks / things to watch

- The `CHALLENGE_TYPE` check in the framework couples Defending Honor's
  challenge-type constant to a generic call site. If we add more challenge
  types where the ability target is chosen at step 1, this `!=` check needs
  to grow into a set check. Acceptable for now.

- The card `_01078` implements `IRiskThatTargetsCharacters` but not the
  matching `isValidTargetForAbility` on the Risk side. The ability-level
  validation lives on the Action class only. Maryam Benu Pleroma (_01186)
  gates on the **Risk's** interface for `EventCharacterTargeted` — she
  doesn't call `isValidTargetForAbility`, just `cancelEvent`, so no
  duplication needed. Flagging in case future Risk-level redirects appear.
