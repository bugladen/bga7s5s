# Arrogant (_03008) Implementation

Implemented Risk card _03008 ("Arrogant", Vodacce). Risk with a City
Action that issues a Combat challenge restricted by Influence and a
Gambling Maneuver that pays out when the actor outclasses the adversary.

## Card Text
> **City Action:** Your performer issues a [Combat] challenge to target
> opposing character with equal or lower [Influence].
> **Gambling Maneuver:** If your participant has more [Influence] than
> the adversary • +1[Riposte] and draw a card.

## Files Touched
- `modules/php/cards/faf/_03008.php` — Risk + IHasActions + IHasManeuvers
  + IRiskThatTargetsCharacters; Actions and Maneuvers arrays wired.
- `modules/php/cards/faf/actions/Action_03008.php` — new.
- `modules/php/cards/faf/maneuvers/Maneuver_03008.php` — new
  (`modules/php/cards/faf/maneuvers/` directory is new for the
  expansion).
- `states.inc.php` — added `"03008" => HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`.

## City Action — `Action_03008`

Modeled on `Action_01083` (Legendary Reputation) since both are Risk
cards whose City Action issues a Combat challenge selected by the
performer. Pattern:

1. `RequiresPerformerSelected = true` — framework asks for the performer
   first.
2. `isAvailableToPlayer` walks the player's city characters that
   `canChallenge()` and checks if any has at least one valid Influence-
   restricted target at the same location.
3. `getPerformersForAction` filters to those same characters so the
   performer chooser doesn't list dead-ends.
4. `isValidTargetForAbility` enforces opposing controller, same
   location, and `target->ModifiedInfluence <= performer->ModifiedInfluence`.
5. `handleEvent` on `EventActionTriggered` sets `CHALLENGE_TYPE =
   NORMAL_CHALLENGE_TYPE`, `CHALLENGE_STAT = STAT_COMBAT`, queues a
   `createTransitionEvent("03008")`. **No `createActionResolvedEvent`
   call** — challenge resolution flow fires it (matches `Action_01083`'s
   comment-only justification, which the pre-commit hook accepts via the
   literal string in the comment).

**WHY `NORMAL_CHALLENGE_TYPE`, not a new type:** Arrogant only restricts
the *target* by Influence, not intervention or refusal. The standard
`HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` state filters via
`IAbilityThatTargetsCharacters::isValidTargetForAbility`, so the
Influence gate is enforced there. No new state, no new challenge type.

**WHY `ModifiedInfluence`, not `Influence`:** stat checks in the city
use the modified value to honor ongoing modifiers (attachments, scheme
effects, location flags). The printed base Influence would ignore those.

## Gambling Maneuver — `Maneuver_03008`

Modeled on `Maneuver_01115` (Taunt's "If your participant has more
Finesse" maneuver) for the comparison gate, and on Aja's Technique
(`Technique_03002`) for the `DUEL_GAMBLED` gating.

`isAvailableToPlayer` gates on:
1. Base `Maneuver::isAvailableToPlayer` (Used flag etc.).
2. `Game::DUEL_GAMBLED` — the "Gambling Maneuver" semantic.
3. Actor and adversary both resolvable.
4. `actor->ModifiedInfluence > adversary->ModifiedInfluence`.

On resolution:
- `EventDuelCalculateManeuverValues`: `$event->riposte += 1` plus an
  explanation line (mirroring Maneuver_01084's pattern).
- `EventResolveManeuver`: queue `createCardDrawnEvent($event->playerId,
  …)` with the owner's inject code as the source.

**WHY draw in `EventResolveManeuver` and not
`EventDuelCalculateManeuverValues`:** the Calculate event can fire
multiple times for recalculation (Maneuver_01061 draws there, but its
draw is conditioned on a property that won't double-fire). For a
straight "draw a card" I followed Maneuver_01084's pattern — fire the
draw once in the explicit Resolve event.

**Pre-commit:** `EventManeuverCanceled handler not needed` comment in
place (no state to undo — the maneuver only adds Riposte and draws,
and the framework rolls back queued events on cancel).

## Pre-commit hook compliance

- `Action_03008 extends RiskCityAction` → hook requires literal
  `createActionResolvedEvent` somewhere in the file. Satisfied by the
  comment on the handleEvent transition (matches Action_01083's
  approach).
- `Maneuver_03008 extends Maneuver` → hook requires either an
  EventManeuverCanceled handler or the `EventManeuverCanceled handler
  not needed` comment. Comment added.
- No ISorcererAbility, no IRangedAbility, no IAbilityThatTargetsCards.
- Class implements IAbilityThatTargetsCharacters on the Action (not
  both interfaces on the same class).

## Traits

`Flourish`, `Hubris`, `Challenge` — all already in
`TraitNames::$TraitsJson`. No additions needed.

## Open Questions

- **Influence gate scope of "your participant".** I read "your
  participant" as the round actor (the player whose combat card
  resolution is currently being computed). `getDuelRoundActor()` is the
  consistent helper used by Maneuver_01115 and others. If "participant"
  is meant in some broader duel sense, this would need revisiting, but
  the convention across the codebase is actor = participant for the
  current round.
- **Intervention/Influence interaction.** The card restricts only the
  initial target by Influence. An intervener with higher Influence is
  still allowed to step in (no card text suggests otherwise). Used
  `NORMAL_CHALLENGE_TYPE` accordingly. Flagging for QA if the printed
  rules clarify intervention scope.
- **Same round, both gambling and Influence advantage.** If the actor
  gambled but lost Influence to a mid-round effect, the maneuver
  isAvailable check would correctly hide the maneuver. The check
  re-evaluates each time isAvailable is called.
