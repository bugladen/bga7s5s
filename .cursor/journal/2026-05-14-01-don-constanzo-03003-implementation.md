# Don Constanzo Scarpa (`_03003`) — implementation

Character (not Leader) in the faf expansion. Vodacce / Red Hand / Tyrant /
Villain. Same name as `_01006` (the original Don Constanzo Leader) but
distinct stats and abilities — this one is the Fearsome Father variant.

## Card text

- **City Action:** Your **Thug** at this location issues a **Combat**
  challenge to target opposing character.
- **City Reaction:** After your **Thug** is destroyed • Put a different
  **Thug** into play at your **Home** from your hand or discard pile, at -1
  cost.

## City Action — `Action_03003`

Pattern F (issue-a-challenge) with a twist: the **performer of the
challenge is not the action's owner**. Don is the action owner but the
challenge is issued *by* one of his Thugs at the same location.

Two-step action:

1. **Step 1 (`HIGH_DRAMA_PLAYER_TURN_03003`):** Player picks one of their
   Thugs at Don's location. Required: `hasTrait("Thug")`,
   `canChallenge()`, not `Engaged`, AND the Thug must have at least one
   opposing character at its location (== Don's location).
2. **Step 2 (`HIGH_DRAMA_PLAYER_TURN_03003_2`):** Player picks an opposing
   character at the chosen Thug's location.

After step 2: set `CHOSEN_PERFORMER = thug->Id`, `CHOSEN_TARGET =
target->Id`, `CHALLENGE_STAT = STAT_COMBAT`, `CHALLENGE_TYPE =
DON_CONSTANZO_CHALLENGE_TYPE` (new), then queue
`createTransitionEvent("03003_2")` → routes to
`HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`.

### New `DON_CONSTANZO_CHALLENGE_TYPE = 19`

Card text imposes no special intervention/refusal restrictions. The
constant exists so other code can tell Don's challenge apart from a
basic NORMAL challenge — useful for analytics and any future
trait-based card that wants to interact with this challenge specifically.

**Don is NOT in `stIssueChallenge`'s auto-engage list.** Engagement is
handled in the action itself (step 2), conditionally: engage the Thug
only if it isn't already engaged. WHY: engaged Thugs are eligible
performers (see "Eligibility" below), so blindly firing
`createCardEngagedEvent` on an already-engaged Thug would re-emit
`EventCardEngaged`, which downstream reactions (e.g. Vittoria's
"instead of me" swap, `Reaction_01014`) treat as a fresh engagement.

WHY a new type rather than `NORMAL_CHALLENGE_TYPE`:

- NORMAL is for the basic challenge action (the city action menu's
  fallback). Conflating Don's "thug-issues-challenge" with NORMAL risks
  unrelated code paths assuming NORMAL means "performer is the action
  owner."
- Mirrors `SERVO_SCARPA_CHALLENGE_TYPE` which also has no special UI
  restrictions but justifies its own type for the same reason.

Integration points touched:

- `modules/php/Game.php` — constant.
- `seventhseacityoffivesails.js` — same int.
- `Theah::interventionCheck` / `ArgumentsTrait::argsHighDrama...` /
  `FrameworkActionsTrait::actHighDramaChallengeActionReject` — **not**
  touched. Card text imposes no restrictions on interventions/refusal.
- `OnUpdateActionButtons.js::highDramaChallengeActionAcceptChallenge` —
  **not** touched. No Refuse-button gating.
- `StatesTrait::stIssueChallenge` auto-engage list — **not** added.
  Action engages the Thug itself, conditionally.

### Eligibility: engaged Thugs are allowed

`getAvailableThugs` filters to Thugs at Don's location with
`canChallenge()` — but does NOT require `! Engaged`. WHY: card text says
"Your Thug at this location issues a Combat challenge" with no engage
cost printed. An already-engaged Thug has effectively paid the
engagement cost in some prior context; the rules don't bar it from
issuing the challenge here. The action just sets up the challenge,
engaging the Thug in the process (if it wasn't already).

Contrast with Aja (`Action_03002`) and Servo (`Action_01011`) which
explicitly check `! Engaged` because their text includes "Engage [self]"
as a printed cost.

### `IAbilityThatTargetsCharacters`

`Action_03003` implements this — the challenge targets a character, so
"before being targeted" hooks need to see it. `isValidTargetForAbility`
validates the target against the *chosen performer's* location (set in
`CHOSEN_PERFORMER` during step 1), not Don's location. They're equal in
the normal flow, but this stays correct if a Thug somehow moved between
steps (defensive).

### `createActionResolvedEvent`

Not called from `Action_03003` — the challenge resolution flow fires it
(cancelled path or threat-resolution path). Comment in the code documents
this. Matches `Action_03002` (Aja) and `Action_01083`.

### `getAvailableThugs` filter

Thugs in play at Don's location, controlled by Don, with `canChallenge()
&& ! Engaged`. WHY both `canChallenge` AND `! Engaged`: `canChallenge()`
checks the trait/keyword "Cannot Challenge" hard-bans; the `Engaged`
check is the engagement-as-cost gate. Both are necessary.

## City Reaction — `Reaction_03003`

Triggered by `EventCharacterDestroyed` when:

- The destroyed character is a **Thug** (`hasTrait("Thug")`).
- The destroyed character was controlled by Don's controller.
- Don is still in play (`! characterIsInDiscardOrLocker(don)` and
  `cardInCity(don)` — City Reaction).
- Don's reaction is available (not used this day).
- The controller has at least one *different* Thug in hand or discard
  pile.

WHY all those gates upfront (`handleEvent`): per Pattern D guidance, the
valid-target precondition must be checked before queuing the reaction
transition, or the player gets a useless Decline prompt. Better to skip
the reaction entirely than to show an empty picker.

Multi-stage button reaction with two stages:

1. **`'pick'`:** One button per eligible Thug (hand and discard pile),
   labeled `Hand: <name> (cost N)` or `Discard: <name> (cost N)`. Plus
   `Decline`.
2. **`'pay'`:** One button per card in the player's hand, labeled
   `Pay with <name> (+N Wealth)`. Plus `< Back` (undo last paid card or
   return to `'pick'` if nothing paid) and `Decline` (abort entirely).
   Skipped entirely when `cost == 0` — picker flows straight to muster.

Stage state is persisted on the Reaction instance (`$stage`,
`$chosenThugId`, `$chosenThugLocation`, `$destroyedThugId`,
`$paidCardIds`, `$paidWealth`, `$paidHasWealthCard`) so re-entries to
`getReactionButtonProperties` after a click see the correct stage. This
mirrors `Reaction_03cd10` (Julius Caligari). Every state mutation calls
`$owner->IsUpdated = true` so the framework persists the new field
values across reaction-loop iterations.

### Putting the Thug into play

On `Confirm`:

```php
if ($chosenThugLocation === playerDiscardDeckName) {
    queueEvent(createCardRemovedFromPlayerDiscardPileEvent(...));
}
queueEvent(createCharacterMusteredEvent($playerId, $thugId, LOCATION_PLAYER_HOME));
setUsed(true);
```

WHY musterEvent for both sources: From hand, `createCharacterMusteredEvent`
handles the hand-to-Home transition; the framework figures the rest
(controller, location, etc.). From discard pile, we must first emit the
`CardRemovedFromPlayerDiscardPile` event to keep listeners that track
discard state in sync. Mirrors Bravos (`Action_01024`) — same shape for
discard-pile-to-play.

### Wealth cost — incremental click-to-pay

The wealth cost is enforced entirely **inside** the Reaction class,
without involving the framework's pay-state machinery. WHY rolled-from-
scratch instead of `PAY_STATE_PLAY_BRUTE`:

- `actPayForBrute` is tied to the high-drama-player-turn cycle (success
  transition → `HIGH_DRAMA_PLAYER_TURN_EVENTS`). But `EventCharacterDestroyed`
  can fire during dawn cleanup, pressure resolution, duel cleanup, etc.,
  so a fixed return state would break those contexts. The
  `playerReaction` loop the reaction is already inside handles return-to-
  invoker correctly.
- `actPayForBrute` also hard-codes `Location == LOCATION_HAND` on the
  card being put into play — wouldn't accept a Thug from the discard pile.

The click-to-pay flow:

1. After the Thug is chosen, if its cost is 0 the reaction skips
   straight to muster.
2. Otherwise `stage = 'pay'`. Buttons show every card in the player's
   hand (excluding the chosen Thug if it's the hand-source one, and
   excluding cards already in `paidCardIds`). Button label includes the
   card's wealth value: `Pay with <name> (+1 Wealth)` or `+2` for a Wealth
   card.
3. Each click runs `handlePay`: validate the card is still in hand,
   append to `paidCardIds`, add its wealth (1 or 2 — Wealth-trait cards
   are worth 2 per `UtilitiesTrait::isValidWealthPayment`), set
   `paidHasWealthCard = true` if applicable.
4. After every click, check `isPaymentComplete($cost)`: exact match OR
   `paidWealth == cost + 1 && paidHasWealthCard`. If complete, queue
   `createCardDiscardedFromHandEvent($asPayment = true)` for every paid
   card, then queue the muster (and `CardRemovedFromPlayerDiscardPile` if
   the Thug is from discard). Reaction marked used. Otherwise re-queue
   the reaction transition for another iteration.
5. `< Back` undoes the most-recent paid card and re-computes the running
   totals from `paidCardIds` (cheaper to recompute than to track an undo
   stack with reversible deltas). If nothing has been paid yet, `< Back`
   instead returns to the `'pick'` stage so the player can choose a
   different Thug.
6. `Decline` aborts entirely — no discards are queued because we only
   queue them at finalize-time, so the player keeps any cards they had
   tentatively selected. This is the WHY behind not queueing discards
   incrementally: it makes Decline a clean rollback.

### Filtering out invalid clicks before they happen

`wouldClickProduceValidPayment($card, $cost)` is checked twice:

- In `getReactionButtonProperties` during the `'pay'` stage to suppress
  buttons that would overpay invalidly (e.g. cost=2, already paid 2 with
  non-Wealth cards, clicking a third non-Wealth card would yield 3 which
  is neither exact nor a valid Wealth-overpay).
- In `handlePay` as defense-in-depth — if a stale button somehow gets
  clicked, the click is silently rejected and the same stage is re-shown.

The rule: clicking is allowed if `newPaid < cost`, OR `newPaid == cost`,
OR (`newPaid == cost + 1` AND we'll have at least one Wealth card after
the click). Anything else is a dead-end, so don't offer it.

### Why discards are queued at finalize, not on click

Two reasons:
1. **Decline is a clean rollback.** No state-mutating events have been
   queued during the `'pay'` stage, so aborting just resets the reaction
   instance state.
2. **Atomicity for downstream listeners.** Other cards that react to
   `EventCardDiscardedFromHand` would fire mid-payment if we queued
   incrementally, making the partial-payment state visible to game
   logic. Atomic discard + muster keeps the reaction's "I'm paying for
   this Thug" semantics intact.

### `IAbilityThatTargetsCharacters` on the reaction

NOT implemented. The reaction puts a Thug *into play* — it doesn't target
a character being attacked/manipulated. No "before being targeted" hook
applies.

### `ISorcererAbility`

NOT implemented. Card text is "City Reaction", not "Sorcerer City
Reaction."

## State IDs

Per `feedback_state_id_encoding.md`:

- `HIGH_DRAMA_PLAYER_TURN_03003 = 403003`
- `HIGH_DRAMA_PLAYER_TURN_03003_2 = 4030032`

No engineering around hypothetical CD-card collisions.

## `states.inc.php` entries

Added both:

- `"03003" => HIGH_DRAMA_PLAYER_TURN_03003` — entered from
  `EventActionTriggered` via `Action_03003::handleEvent`.
- `"03003_2" => HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` — entered
  from step 2 via `createTransitionEvent("03003_2")`. This is the Pattern
  F exception to the "don't add `_2` blindly" rule, because the action
  *does* queue a `_2` transition event to cross from the picker into the
  challenge sub-state machine.

## State files

- `modules/php/States/faf/State_highDramaPhase03003.php` — step 1 picker.
- `modules/php/States/faf/State_highDramaPhase03003_2.php` — step 2 picker.

Both have `"zombie"` and `"thugChosen"`/`"targetChosen"` transitions out
to keep the zombie path named and distinct (per the named-transition rule
in `create-character` SKILL.md).

## JS wiring

Three handlers per state in:

- `modules/js/OnEnteringState.faf.js` — highlight performer + selectable
  targets.
- `modules/js/OnUpdateActionButtons.faf.js` — `Confirm` button +
  disabled-by-default.
- `modules/js/OnLeavingState.faf.js` — cleanup highlights.

`donId` (step 1) vs `performerId` (step 2) — distinguished by JS naming
since step 1 highlights Don himself as the "anchor" (the action owner),
while step 2 highlights the chosen Thug (which IS the challenge performer).

## Files touched

- `modules/php/cards/faf/_03003.php` — wired interfaces, Actions/Reactions
  arrays.
- `modules/php/cards/faf/actions/Action_03003.php` — new.
- `modules/php/cards/faf/reactions/Reaction_03003.php` — new.
- `modules/php/States/faf/State_highDramaPhase03003.php` — new.
- `modules/php/States/faf/State_highDramaPhase03003_2.php` — new.
- `modules/php/States.php` — two new constants.
- `modules/php/Game.php` — `DON_CONSTANZO_CHALLENGE_TYPE = 19`.
- `modules/php/StatesTrait.php` — added to auto-engage list.
- `seventhseacityoffivesails.js` — JS challenge type constant.
- `states.inc.php` — two transition-name mappings (`"03003"` and
  `"03003_2"`).
- `modules/js/OnEnteringState.faf.js`,
  `modules/js/OnUpdateActionButtons.faf.js`,
  `modules/js/OnLeavingState.faf.js` — handlers for both new states.

## Pre-commit hook compliance

- `Action_03003 extends CharacterAction` — hook regex matches only
  `CardAction|RiskAction|RiskCityAction`, so the `createActionResolvedEvent`
  check is not triggered. The challenge flow fires it (matches Aja).
- `Reaction_03003 extends CardReaction` — hook requires `$this->setUsed(`
  and `$this->isAvailable(`, both present.
- No `ISorcererAbility`.
- Action implements only `IAbilityThatTargetsCharacters`, not both
  interfaces.

## Open questions / risks

- **Two Don Constanzos.** The Leader (`_01006`) and this character
  (`_03003`) share the same Name. The faction-deck character is
  distinct gameplay-wise (different stats, different abilities), but the
  log readout will show the same name. Consider whether the Title
  ("Fearsome Father" vs whatever 01006's title is) needs to be more
  prominent in logs. Not a fix here — flagging.

- **Don himself doesn't engage when the action fires.** CharacterAction
  doesn't auto-engage the owning character (verified — the
  `actHighDramaInPlayActionConfirm` central handler does setUsed but not
  engage). Only the challenge performer (the Thug) auto-engages via the
  new challenge type. This matches how Servo Scarpa's action works (Servo
  is both action owner AND challenge performer; only one engagement
  applies). For Don, the question is whether his City Action should
  ALSO engage him as a cost. Card text doesn't say, and standard
  semantics for City Actions in this codebase don't auto-engage the
  owning character. Leaving as-is.

- **Thug at Don's Home.** "Your Thug at this location" — Don's Home
  counts as a location. If Don is at Home with a Thug, the City Action's
  `cardInCity` gate stops it because Home isn't a city location. So the
  action is only available when Don is *in the city*. That matches the
  "City Action" framing.

- **Reaction's wealth cost handling.** Implemented end-to-end via the
  click-to-pay flow described above. Per-user-request the payment is
  tracked entirely inside the Reaction class — no new pay state, no
  changes to `actPayForBrute`/`actPayForReaction`. Each click is one
  reaction-loop iteration. Tradeoff: this puts wealth-payment logic in a
  card class rather than the framework, but it's a one-off pattern (no
  other reaction needs "pay for an arbitrary chosen card from
  hand/discard") so the duplication cost is acceptable.

- **Reaction firing during pressure or dusk.** Verified the
  `handleEvent` checks `cardInCity($don)` AND
  `! characterIsInDiscardOrLocker($don)` — Don can be at Home during dusk
  cleanup, so `cardInCity` correctly skips the reaction in that case.
  The card text says "City Reaction", so it should only fire when Don is
  in the city.

- **Destroyed Thug at a *different* location than Don.** Card text just
  says "your Thug" — no location restriction. My implementation matches
  that (no location check on the destroyed Thug). The new Thug always
  enters at Don's player Home regardless of where the destroyed one was.

- **`hasTrait("Thug")` vs `extends Brute`.** The four base-set Thug cards
  all extend `Brute`. But other expansions could introduce non-Brute cards
  with the Thug trait. The trait check is authoritative; the class is
  not.
