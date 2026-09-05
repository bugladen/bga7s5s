# Daniella _03013 Continuous Reaction (Sorcerer grant)

## Context
Eddie asked for a Continuous Reaction on `_03013` that:
1. Triggers on any event with `sourceId == Daniella.Id`
2. Also triggers when a Maneuver/Technique is played with Daniella as the duel/challenge actor
3. Lets the player grant an opposing character (same location) Sorcerer until end of turn

Clarified: actor is `_03013` (not `_03103` typo). Keep `Action_03013` — do not replace it.

## What shipped
- `Reaction_03013a.php` — Continuous Reaction (no `setUsed(true)`; hook literal kept in comment)
- Wired into `_03013::$Reactions` alongside existing discount `Reaction_03013`
- `Action_03013` left untouched initially (player Continuous Action + EXTRA_ACTIONS)

## WHY this shape

**Reaction not Action for the "may" choice:** Printed text is "may be considered Sorcerers" — player chooses a target. Pattern D Continuous Reaction with Pass fits better than auto-tagging everyone. Action_03013 remains as the existing Continuous Action Eddie wanted kept.

**Broad `property_exists(..., 'sourceId')` gate:** Eddie asked for any event that carries `sourceId == her Id`. Ability effects (wound/move/engage/etc.) stamp sourceId as the causing card. Null/0 skipped (framework).

**Separate Maneuver/Technique actor path:** `EventManeuverActivated.ownerId` is the *combat card*, not the duel actor. `EventTechniqueActivated.ownerId` is often the actor/owner card. Without the actor path, combat-card maneuvers while Daniella is actor would never trigger via sourceId. Challenge-time techniques still match via `ownerId == Daniella` when no duel round actor exists yet.

**Activated not Resolve:** Using `EventManeuverActivated` / `EventTechniqueActivated` only — Resolve would double-prompt on the same play.

**Re-entrancy guard (`$SuppressNextSourceTrigger`):** `CardReaction::performReaction` stacks `EventReactionActivated` with `sourceId = owner`. Without suppressing that one event, granting Sorcerer immediately re-queues this same Continuous Reaction. Pass does not emit ReactionActivated, so no suppress needed there.

**Trait tracking:** `$TaggedCharacterIds` + skip `hasTrait("Sorcerer")` — same dedup WHY as Action_03013 / pattern-a (addTrait does not dedupe; removeTrait removes one entry). Clear only on `EventPlayerTurnEnd` per "until end of turn."

## Overlap with Action_03013
Both can grant Sorcerer in the same turn. Eligible targets for the Reaction exclude anyone who already has the trait (including Action-tagged). Fine if Eddie later consolidates; he explicitly said keep the Action.

**2026-09-05 follow-up:** Reaction must NOT trigger on Action_03013 itself. Skip `EventActionActivated` / `EventActionTriggered` when `getAbilityById(actionId) instanceof Action_03013`. WHY: that Action already grants Sorcerer — reacting would double-prompt for the same ability use.

## Risk / watch
Multi-effect abilities that emit several `sourceId == Daniella` events in one batch will queue multiple prompts. Literal reading of Eddie's trigger request. Pass is always available. If it feels spammy in play, debounce per ability/batch later.

## 2026-09-05 — Action_03013 choose-one (not bulk tag)

Eddie: Action should choose which character to grant Sorcerer to, not grant all opposing.

**What changed:**
- `EventActionTriggered` → transition `"03013"` into `HIGH_DRAMA_PLAYER_TURN_03013` (picker) instead of tagging everyone
- Player picks one eligible opposing character at Daniella's location; `addTrait` + track in `$TaggedOpposingIds`
- Still sets `EXTRA_ACTIONS = 1` on trigger (Continuous — don't consume normal HD action)
- Availability now requires ≥1 eligible target (not already Sorcerer / not already Action-tagged)
- No `IAbilityThatTargetsCharacters` — printed text has no "target"; private `isEligibleTarget` / `getEligibleTargets` like Action_03071
- State + JS wired Sanjay `_03037` shape (highlight + Confirm)
- Untag lifecycle unchanged (turn end / Daniella move / Daniella destroyed)

**WHY choose-one on Action too:** Matches the printed "may be considered" intent and the Reaction's one-at-a-time grant. Bulk-tagging everyone was over-reading Continuous as "apply to all present." Reaction skip of Action_03013 still correct — Action already prompts for the grant.

**Not changed:** Reaction_03013a trigger rules, Technique_03013.

## 2026-09-05 — Continuous setUsed(false) on trigger

Eddie: Action is Continuous — ensure `setUsed(false)`.

**WHY on EventActionTriggered (not only turn-end):** Central `actHighDramaInPlayActionConfirm` sets `Used=true` before the trigger event. Waiting until turn end made the Action once-per-turn. Mirror `Action_01090`: flip false immediately on trigger so it stays menu-available. Removed the redundant turn-end/move `setUsed(false)` resets (untag on those events kept).

## 2026-09-05 — still triggering off Action_03013 + stack priority

Root cause: Action_03013 queues `createTransitionEvent(..., "03013", $this->Id)` with `sourceId = Daniella`. First exclusion only covered Activated/Triggered; broad sourceId gate still matched that `EventTransition`.

Fix in Reaction_03013a:
1. `eventSourceIsOwner` returns false for all `EventTransition` — transitions are state routing, not ability-effect sources
2. `isFromAction03013` also matches transition `"03013"` / internalId → Action_03013
3. Reaction transition uses `stackEvent` (priority min-1 bump) like Reaction_03013 discount — Eddie asked for stacked highest priority

## 2026-09-05 — duelChooseAction Sorcerer opt-in

Eddie: add a duel action option from `duelChooseAction` only for Daniella in a duel; makes the adversary a Sorcerer.

**WHY hub button (not only Continuous Reaction):** `Technique_03018` (Suffer Not the Wicked aura) gates `isAvailableToPlayer` on `adversary->hasTrait("Sorcerer")`. That check runs when the Technique list is built from the hub. Reaction_03013a fires on Maneuver/Technique *Activated* — after the player already chose Technique — so it cannot unlock a Sorcerer-gated Technique for the same pick. Tagging at the hub first is required.

**What shipped:**
- `Action_03013::isAvailableAsDuelAction` — IN_DUEL + Daniella at actor's location (same controller) + adversary not already Sorcerer/Action-tagged
- `Action_03013::findAvailableDuelAction` — scans characters at actor location (Daniella need not be the actor)
- `Action_03013::actDuelConsiderAdversarySorcerer` + shared `grantSorcerer` helper (HD picker reuses it)
- `argsChooseDuelAction` exposes `considerAdversarySorcererAvailable`
- `actDuelActionConsiderAdversarySorcerer` in FrameworkActionsTrait
- `states.inc.php`: possibleaction + self-loop transition `considerAdversarySorcerer` → DUEL_CHOOSE_ACTION (refresh args / Technique list)
- JS button in `OnUpdateActionButtons.faf.js` (additive on top of core hub buttons)

**WHY explicit `updateCardObjectInDb`:** self-loop does not run the event hub, so IsUpdated (adversary trait + Daniella's `$TaggedOpposingIds` on the Action) would not flush. HD path relies on ActionResolved through events.

**WHY no ActionActivated:** would be redundant with Reaction skip logic and could still confuse other listeners; grant is the whole effect. Untag still via Action's turn-end / Daniella leave handlers.

**Not emitting ActionResolved** on duel path — not a High Drama action resolution; staying on duel hub.

## 2026-09-05 — duel opt-in without Daniella as participant

Eddie: available during a duel even if Daniella is **not** in the duel, as long as she is at the same location as the actor.

**WHY:** Ability is "while using *your* abilities" with opposing-Daniella scope — a controlled crewmate can be the actor; Daniella only needs co-location so the adversary opposes her. Prior gate `actor === Daniella` was too narrow.

**Change:** `isAvailableAsDuelAction` now requires same `ControllerId` + same `Location` as actor (not identity). Args/act look up via `findAvailableDuelAction` over characters at the actor's location instead of `$actor->getActions()`.

## 2026-09-05 — Sorcerer duration is co-location, not end of turn

Eddie: per `_03013` text, the grant lasts while the character is at Daniella's location; if they move away, remove Sorcerer.

**WHY drop EventPlayerTurnEnd clear:** prior turn-scope came from reading "while using your abilities" as duration. Eddie clarified duration = opposing/co-location. Turn-end clear would strip the trait while still co-located.

**What changed (Action_03013 + Reaction_03013a):**
- On `EventCardMoved` of a tagged id: if `toLocation !== Daniella.Location`, `untagCharacter` (uses toLocation because mover's Location is still fromLocation during handleEvent)
- Daniella move / Daniella destroyed: clear all tags (unchanged intent)
- Tagged character destroyed: drop that id from the list
- Notify / reaction description / duel tooltip: "while at Daniella's location" instead of "until end of turn"
