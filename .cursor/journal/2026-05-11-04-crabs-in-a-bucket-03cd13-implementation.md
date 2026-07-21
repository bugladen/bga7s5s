# Crabs in a Bucket (_03cd13) Implementation

## Card Text

- **Forced:** When this card is revealed • Each player with fewer Renown than the player with the most Renown draws a card.
- **City Action:** If you have fewer Renown than target player, engage your performer • Claim this location.

## Files Touched

- `modules/php/cards/faf/_03cd13.php` — Card class. Forced handler queues `createCardDrawnEvent` for every player whose Renown < max. Defines `getCrabsInABucketUsedListData` delegate (mirrors `_01179::getSirensScreamUsedListData`).
- `modules/php/cards/faf/actions/Action_03cd13.php` — New `EventCityAction implements IAbilityThatTargetsPlayers`. Holds `private array $playersUsed`. `isAvailableToPlayer` checks the player hasn't already used today AND that at least one other player has more Renown. `handleEvent` queues a `03cd13` transition on `EventActionTriggered` and clears `playersUsed` on `EventDuskEndOfDay` (broadcasts via `notifyUsedList`). After target chosen: engage performer + `createLocationClaimedEvent` + push playerId to `playersUsed` + `createActionResolvedEvent` + `setUsed(false)` (defensive — mirror of _01179 to keep the card from being marked single-use).
- `modules/php/States/faf/State_highDramaPhase03cd13.php` — Active-player state. Single transition `playerChosen → HIGH_DRAMA_PLAYER_TURN_EVENTS` plus `zombie`. `actFromCardWithId` is the only `#[PossibleAction]`.
- `modules/php/States.php` — Added `HIGH_DRAMA_PLAYER_TURN_03CD13 = 4030013`.
- `states.inc.php` — Added `"03cd13" => States::HIGH_DRAMA_PLAYER_TURN_03CD13` under `HIGH_DRAMA_PLAYER_TURN_EVENTS`.
- `modules/js/OnEnteringState.faf.js` — Adds `_7sfs-chosen` to the card image, stashes `clientStateArgs.crabsCardId` so leave can clean up.
- `modules/js/OnUpdateActionButtons.faf.js` — Renders one button per eligible target player (server filters to "has more Renown than me").
- `modules/js/OnLeavingState.faf.js` — Removes the `_7sfs-chosen` class (unlike `03cd03` which discards the card and doesn't clean up).
- `modules/js/Templates.js` — `jstpl_crabs_in_a_bucket_used_list` (reuses `_7sfs-card-player-list` CSS).
- `modules/js/Utilities.js` — `displayCrabsInABucketUsedList` / `removeCrabsInABucketUsedList` (copy of the sirens-scream pair).
- `modules/js/Notifications.js` — Registers `crabsInABucketUsedListUpdated` channel + handler.
- `modules/js/Setup.js` — Renders `gamedatas.crabsInABucketUsedList` on page load.
- `modules/php/Game.php` — `getAllDatas` adds the `crabsInABucketUsedList` entry.

## WHY: Followed _01179 instead of _03cd03

The user explicitly asked for the Siren's Scream (`_01179`) pattern: each player can take the City Action once per Day, tracked via a private `playersUsed` array on the Action class, no `setUsed(true)`. `_03cd03` was the wrong reference here because that card is a one-shot — it discards on first use and uses the `chanceMeetingRemaining` global to drive an Initiative-order loop. Crabs in a Bucket explicitly stays on the city location after a player claims it (the action is "claim this location," not "discard this card"), so multiple players can use it across the Day.

The card text doesn't literally say "once per Day," but the user instruction does — and it matches the *spirit* of "engage your performer" (engaging is normally a one-shot cost). Without a once-per-day cap a player could just re-engage a different performer to keep claiming.

## WHY: setUsed(false) defensive call

Lifted directly from `Action_01179`. `setUsed(true)` would mark the card-level ability as consumed and the framework would never let any player use it again. We want the *opposite*: it's the per-player `playersUsed` array that gates availability, not the card's `Used` flag. The `setUsed(false)` is technically a no-op (we never set it true) but I kept it to match precedent and signal intent.

## WHY: target picker even though "If you have fewer Renown than target player"

"Target player" is the standard 7th Sea wording for "you choose a player to target." So the action needs an interactive target picker. The server filters eligible targets to "has strictly more Renown than the active player" before sending the button list, so the rule is enforced both client-side (only eligible buttons appear) and server-side (`handleTargetChosen` re-validates).

Eligibility is also enforced upstream in `isAvailableToPlayer` — if no player has more Renown, the City Action button itself doesn't appear.

## WHY: createCardDrawnEvent for the Forced

I considered queuing one event per player vs. some batch event. `createCardDrawnEvent(playerId, reason)` is the canonical "this player draws a card" event and is what cards like `_01008`, `Action_02034`, `Maneuver_02054` use. The `reason` argument shows the card injection code in the log so the draw is attributed to Crabs in a Bucket.

Edge case: max Renown == 0. Then `< maxReknown` is false for everyone, so nobody draws. That matches the text — "fewer than the most" reads as "strictly less than max," and if everyone is tied at 0, nobody draws. Probably fine.

## WHY: Notification + page-refresh plumbing

Direct copy of the Siren's Scream UI work from 2026-03-28. The `_7sfs-card-player-list` CSS class is already shared between siren's-scream, location-action, and cats-embargo, so no CSS additions needed. The notification channel speed is `1` (instant) — these are UI updates, not animated events.

## Things to Verify in Play

- Forced fires once when the card is added to the city. `EventCityCardAddedToLocation` is the same trigger `_01179` uses; should be reliable.
- Active-player switching when a non-active player wants to use the City Action: this is handled centrally by the High Drama events loop (each player's turn). My state only activates while the player whose turn it is is the one acting.
- Page refresh in mid-Day: `getAllDatas` will include the `usedList` and `Setup.js` will render it on the card. Verified by inspection that this matches the Siren's Scream code path.

## Not Done

- No CSS changes (reused `_7sfs-card-player-list`).
- No new pressure flag (this card doesn't modify pressure).
- No second state — single-step target picker is enough.
