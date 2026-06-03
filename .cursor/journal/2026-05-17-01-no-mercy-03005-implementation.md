# No Mercy (_03005) - Vodacce Scheme Implementation

## Card text

- **Resolve:** Add a Renown to [Bazaar] and [Forums]
- **Resolve:** Put a **Gang**, **Crime**, or **Villainous** card from your discard into your hand.
- **Reaction:** After your **Red Hand**'s challenge is refused • Claim that location.

Vodacce scheme. Initiative 91, Panache -1. Traits: Villainous, Duress.

## Files touched

- `modules/php/cards/faf/_03005.php` — finished the scheme class with
  ReactionTrait + IHasReactions, `handleEvent` for `EventResolveScheme`,
  `actFromCardWithId` (pick from discard), `actFromCardPass`.
- `modules/php/cards/faf/reactions/Reaction_03005.php` — new.
- `modules/php/States/faf/State_planningPhaseResolveSchemes03005.php` —
  new GameState class for the discard-pick state.
- `modules/php/States.php` — added
  `PLANNING_PHASE_RESOLVE_SCHEMES_03005 = 2603005`.
- `states.inc.php` — added `"03005"` transition mapping in
  `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS`.
- `modules/js/OnEnteringState.faf.js`,
  `modules/js/OnUpdateActionButtons.faf.js`,
  `modules/js/OnLeavingState.faf.js` — JS hooks for
  `planningPhaseResolveSchemes_03005`.

Zombie handling lives on the new `State_planningPhaseResolveSchemes03005`
class's `zombie()` method — no edit to `ZombieTrait.php` needed (only
the older inline `states.7s5s.php`-based states are dispatched there).

## Resolve flow — two Renowns + transition

Modeled after `_01044` (Armed and Marshaled): the resolve handler queues
the two `createRenownAddedToLocationEvent` calls (Bazaar + Forum) then
queues a `createTransitionEvent` to move into the discard-pick state.
The Renown adds resolve first because they're queued first; the
transition has `MEDIUM_PRIORITY` so reaction-triggers don't preempt it.

## Discard-pick state — new GameState class pattern

WHY use the `States/faf/State_planningPhaseResolveSchemes03005.php`
class pattern (à la `_02046`/`_02052`) rather than the inline
`states.7s5s.php` entry (à la `_01044`/`_01045`): the new pattern is
what recent scheme work uses, gives type-checked `PossibleAction`
binding, and supplies a `zombie()` method directly on the state.

Still required the `"03005" => States::...` mapping in
`states.inc.php` so the dispatch from
`PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS` knows where to route.

### Trait-filter for the pick

JS filters `player.discard` by `card.traits.includes('Gang') ||
'Crime' || 'Villainous'`. PHP-side `actFromCardWithId` re-validates
with `$card->hasTrait(...)` because clients can't be trusted.

### Pass guard

`actFromCardPass` walks the discard pile and throws if *any* eligible
card is present — same guard as `_01044`. Card text says "Put a … card
from your discard into your hand" with no "if able" qualifier so the
player must take one if able. The Pass button is JS-disabled when the
list is non-empty, but the server guard is the authority.

## Reaction — `Reaction_03005`

Triggers on `EventChallengeRejected`. The event has only
`challengerId` and `targetId`; the location of the challenge is taken
from `challenger->Location` at trigger time (this is the city location
where the challenge was issued, since challenges happen as City
Actions at the challenger's location).

### Gates
1. `$this->isAvailable()` — once per day, reset by
   `EventDuskEndOfDay` in `CardReaction::handleEvent`.
2. Challenger is controlled by the scheme controller (`"your Red
   Hand"`).
3. Challenger has the `Red Hand` trait.
4. Challenger's location resolves to a real city location (defensive
   — should always be true for a city-issued challenge).

### Location stored on the reaction object

`private string $location` is set when the event triggers and consumed
by `performReaction`. Cleared after the reaction resolves (pass or
claim) to avoid stale state on a later trigger. `$owner->IsUpdated =
true` ensures the field is persisted to DB.

### "Claim that location"

`createLocationClaimedEvent($owner->ControllerId, null, $location)` —
performerId is null because the claim isn't tied to a specific
performer (compare `Action_03cd13.php` which passes the performer for
its claim; here the card text doesn't reference a performer for the
claim, the trigger is just the refusal).

### Pattern source

- `Reaction_01116a` (Yevgeni's en garde-on-refusal) — the
  `EventChallengeRejected` listener shape, including the
  `createReactionTransitionEvent` queue.
- `Reaction_02004` (Crash the Party's City Reaction) — the
  scheme-with-reaction shape and the per-reaction `$location` field.
- `Action_03cd13.php` — the `createLocationClaimedEvent` usage.

## Pre-commit hook compliance

- `extends Scheme` (no Action/Reaction *subclass* requirements on the
  scheme itself — `setUsed`/`isAvailable` requirements are on
  `CardReaction/AttachmentReaction` subclasses, which `Reaction_03005`
  is, and both are present).
- `Reaction_03005::performReaction` calls `$this->setUsed(...)` and
  `handleEvent` calls `$this->isAvailable()` — hook satisfied.
- No `ISorcererAbility`, no `IAbilityThatTargetsCharacters` (the claim
  doesn't target a character, it targets a location).
- No `createAttachmentEquippedEvent`.

## Open questions / risks

- **Scheme reactions during High Drama phase.** Challenges happen in
  High Drama. Verified the `$theah->cards` array (populated by
  `buildCity()`) includes cards from discard piles, so once the
  resolved scheme is in discard the reaction's `handleEvent` should
  still be called. `_02004` has a Reaction on a Scheme and works in
  production, so the lifecycle is trusted.

- **Multiple refusals same day.** Reaction is once-per-day via
  `isAvailable()`/`setUsed`. After the first claim, subsequent
  refusals won't trigger until `EventDuskEndOfDay`. Correct per
  standard reaction semantics.

- **Location already controlled by you.** `createLocationClaimedEvent`
  sets the controller unconditionally — re-claiming your own location
  is a no-op effectively but the notification will still fire. No
  guard added because card text doesn't restrict.

- **Challenger destroyed before reaction prompt.** If the challenger
  is destroyed between the `EventChallengeRejected` and the reaction
  state, the stored `$this->location` is still valid because we
  captured the location string at trigger time. We don't dereference
  the challenger again in `performReaction`.
