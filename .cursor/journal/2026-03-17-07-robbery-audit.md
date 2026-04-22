# Robbery (_01113) Audit

## What I found

### First pass: Two bugs in the action/maneuver implementation

1. **Duplicate event in Action_01113.php** — `createCardRemovedFromPlayerDiscardPileEvent` was queued twice in `actFromActionWithId` for the `HIGH_DRAMA_PLAYER_TURN_01113_2` state (originally lines 216-217 and 222-223). Pure copy-paste artifact. Would fire the "card removed from discard pile" event handler twice for the same card, which could cause double-counting or notification issues depending on what listeners are attached.

2. **Missing CHOSEN_PERFORMER in Maneuver_01113.php** — `actFromManeuverWithId` never set `Game::CHOSEN_PERFORMER` before queuing `EnteringPayStateEvent`. The pay state handler reads `CHOSEN_PERFORMER` to calculate equip discounts via `getEquipDiscount($performer, $attachment)`. In duel context, `CHOSEN_PERFORMER` holds whatever was set during the challenge phase (typically the challenger). If the *defender* is the one using this Pirate Maneuver, the discount calculation would use the challenger's stats instead of the defender's. Fixed by adding `$game->globals->set(Game::CHOSEN_PERFORMER, $actor->Id)` before the event queue.

### Second pass: Night of Drinking cancellation + Maneuver not available during duel

Eddie reported: Robbery action used, Night of Drinking cancelled it, then gambled into Robbery during a duel. Pirate Maneuver wasn't available in the UI. Eddie confirmed the character has Pirate trait.

**Bug #3 found and fixed: `argsDuelUseManeuverFromCombatCard()` incorrectly checked `isAvailable()` (Used flag)**

Combat card maneuvers should ALWAYS be available regardless of the `Used` flag. `hasManeuversAvailableToPlayer()` correctly checks only `isAvailableToPlayer()`. But `argsDuelUseManeuverFromCombatCard()` called `getManeuversArray($this, $mustBeAvailable = true)` which checks BOTH `isAvailable()` (Used flag) AND `isAvailableToPlayer()`. If the maneuver's `Used` flag was true (e.g., from a previous duel round), the maneuver would be filtered out of the args even though the gate check passed.

Initially I incorrectly tried to fix `hasManeuversAvailableToPlayer` by adding the `isAvailable()` check there. Eddie corrected me: maneuvers on combat cards don't need the Used flag check. The real fix was in `argsDuelUseManeuverFromCombatCard` — changed from `getManeuversArray($this, $mustBeAvailable = true)` to using `getManeuversAvailableToPlayer($this, $playerId)` which only checks `isAvailableToPlayer()`, matching the gate check behavior.

WHY this was the root cause of Eddie's bug: The Maneuver base class sets `Used = true` on `EventManeuverActivated`. If any event processing left this flag set (or it was set from a prior duel round and not reset), the maneuver would pass the gate but fail the args population.

**What set the maneuver's Used flag?**

The Maneuver base class sets `Used = true` on `EventManeuverActivated` and resets on `EventDuelEnd`. In Eddie's scenario, the most likely explanation: the `Used` flag was set from a prior context and not properly reset before the card was gambled into. The cancellation by Night of Drinking causes the card to go through an unusual lifecycle (hand → purgatory → discard pile → deck reshuffle → gambled), and during this path the `EventDuelEnd` reset might not reach the card's maneuver if the card wasn't in Theah's cards array at the right time.

## WHY decisions

For Bug 1: Removed the first duplicate (before the globals->set calls) and kept the second one which sits in the correct position in the event queue: after setting globals, before the addToHand and enterPayState events. The maneuver's version has them in this order and doesn't have the duplicate, confirming this is the intended sequence.

For Bug 2: Placed the `CHOSEN_PERFORMER` set immediately after `addCardToWorld` and before the conditional unequip/discard block. This ensures it's set regardless of which branch (adversary-attached vs discard-pile) the card came from, and before any events that might read it.

For Bug 3: Changed `argsDuelUseManeuverFromCombatCard` from using `getManeuversArray($this, $mustBeAvailable = true)` to using `getManeuversAvailableToPlayer($this, $playerId)` which only checks `isAvailableToPlayer()`. Combat card maneuvers should always be available regardless of the Used flag — the gate check (`hasManeuversAvailableToPlayer`) was already correct in not checking it.

## Architecture notes for future agents

- `eventCheck` vs `handleEvent`: eventCheck runs when events are QUEUED (mostly no-ops on abilities); handleEvent runs when events are PROCESSED in runEvents
- `queueEvent` internally calls `eventCheck` before adding to DB
- `runEvents` stops processing and RETURNS when it hits an EventTransition — remaining events stay in queue
- Card objects loaded from `getCardObjectFromDb` are SEPARATE from objects in `Theah->cards` (loaded by buildCity). Updates to one don't affect the other
- `buildCity()` runs once per Game instance (guarded by cityBuilt flag). In BGA framework, each HTTP request creates a new Game instance
- The `deleteActionTriggeredEvents` SQL uses LIKE pattern matching on serialized event data — only deletes EventActionTriggered events, not EventTransition or other event types

## Session 2: Deeper investigation with Eddie's game state details

Eddie provided the full scenario details:
- First Robbery played from hand for City Action, cancelled by Night of Drinking
- Second Robbery drawn from faction deck via gamble mechanic during duel
- Character has Pirate trait ✓
- Only Uppman's Jacket equipped (Attire trait, not Weapon) ✓
- Gallegos Blade (_01101) in adversary's discard pile, costs 0, has Weapon trait ✓
- 9 cards in hand ✓
- Makepeace Botwighte in play for opponent

**Exhaustive code analysis — no definitive root cause found**

I traced every check in `Maneuver_01113::isAvailableToPlayer` and `attachmentsAvailableFromOpponentDiscardPile`:
- `parent::isAvailableToPlayer` → always returns true
- Pirate trait check → true (confirmed)
- `getDuelRoundActor()` → should return Eddie's character (actor alternates, gamble is only available to the actor)
- `handWealthCount` → at least 9
- Gallegos Blade (cost 0) → even with Makepeace's +1 cost increase, effective cost is 1, which 9 hand wealth easily covers
- `hasEquipRestrictions` → no Weapon on character (only Attire), so no restriction
- `canAttachTo` → Gallegos Blade doesn't override, returns true
- `FactionAttachment.CanEquipToOpponents` → not checked in Robbery's flow at all
- DB query for discard pile cards → should find Gallegos Blade if it's at the right location

**Possible explanations I couldn't confirm without live game data:**
1. Gallegos Blade was not actually at the expected `card_location` in the DB (data issue)
2. The card's serialized data was corrupted and `safeUnserialize` failed silently
3. Some interaction between `buildCity()` timing and event processing modified state
4. The duel round actor was not who Eddie expected (unlikely since gamble is only from DUEL_CHOOSE_ACTION which is the actor's state)

**Added debug logging** to both `Maneuver_01113::isAvailableToPlayer` and `Theah::attachmentsAvailableFromOpponentDiscardPile` using `$game->warn()`. Next time this occurs, the BGA server logs will show exactly which check failed and why.

## Frustration note

This was genuinely frustrating. I went through every code path multiple times with all of Eddie's constraints and couldn't find a logical reason for the maneuver to be unavailable. With Pirate trait, 9 cards in hand, no Weapon equipped, and a cost-0 attachment in the discard pile, every check should pass. The debug logging is the pragmatic answer here — the bug is likely data-dependent and needs to be caught in the act.

## Things I verified are correct

- City Action correctly: requires Pirate performer, targets only opponent discard pile attachments, validates location/controller, pays costs via the standard pay state flow
- Maneuver correctly: requires Pirate actor, targets both adversary-attached AND discard pile attachments, validates both locations, handles unequip+discard flow for adversary attachments before routing through discard pile removal
- The maneuver's event flow for adversary-attached cards (unequip → discard from play → remove from discard → add to hand → pay → equip) is intentionally routing through intermediate states to fire all relevant event handlers
- State files, JS handlers (entering/leaving/update buttons/event handlers) are all correctly wired
- Payment states properly display cost chips and handle wealth selection
- Back button on action state _01113_2 properly transitions back to _01113
- Night of Drinking cancellation correctly deletes ActionTriggered events, preventing the action from resolving. The CardDiscardedFromHand event is NOT deleted, which appears intentional (card is still "played" even if cancelled)
- Ability IDs are unique per card instance (include DB card ID), so two Robbery copies have distinct maneuver IDs
- `Used` flag on second Robbery should be false (card was in faction deck, never in `$theah->cards`, never processed by event handlers)
