> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Cross-Cutting Helpers

- `$theah->getCharactersInCityByPlayerId(int $playerId): array` — characters of `playerId` currently at city locations.
- `$theah->getCharactersInPlayByPlayerId(int $playerId): array` — wider net: characters in city or home.
- `$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array` — opposing characters at a location.
- `$theah->getCharactersAtLocation(string $location): array` — everyone at a location (defensive: filter by `isControlled()` and `ControllerId` when "opposing" is the intent).
- `$theah->cardInCity(Card $card): bool` — true when the card is at a city location.
- `$theah->getDuelRoundActor(): ?Character` / `getDuelRoundOpponent(): ?Character` — the round's participant + adversary.
- `$theah->getCurrentDuelThreat(int $characterId): int` — this round's `ending_<side>_threat` for a duel participant. Use for Pattern C.6 move/remove-all-threat calcs (`Maneuver_03048`, `Technique_02012`).
- `$theah->getDuelChallengerId() / getDuelDefenderId() / getDuelOpponentId(int $actorId)` — id-only accessors. **All three return CHARACTER ids, not player ids.** Looking up a player from one of these requires `$theah->getCharacterById($id)->ControllerId`. Don't pass them to `getPlayerNameById($playerId)` — you'll print "0" or worse. The `challenger_id` / `defender_id` columns in the `duel` table are character primary keys (the dueling characters), not player primary keys.
- `Game::IN_DUEL` global — true between duel start and end.
- `Game::DUEL_GAMBLED` global — true after the actor locks in a combat card via gamble; cleared at end of round.
- `Game::CHOSEN_PERFORMER` / `CHOSEN_TARGET` / `CHALLENGE_TYPE` / `CHALLENGE_STAT` globals — set in `handleEvent` on `EventActionTriggered` to brief the challenge sub-state machine. `stIssueChallenge` auto-engages only for `NORMAL` / `SERVO_SCARPA` / `TORVO_ESPADA` / `AJA` — when the Action pays engage itself, mint a type **off** that list (also doubles as a refuse/intervene correlator; Pattern A.5 / `_03057`).
- `Game::EXTRA_ACTIONS` — integer counter read in `stNextPlayer`. When `> 0`, the current player takes another turn instead of advancing. Decremented each time `stNextPlayer` runs. Set by cards that grant "an extra action" (e.g., `Action_01090`, `Action_03013`). **Alone, this only keeps the same player — not the same performer.**
- `Game::EXTRA_ACTION_PERFORMER` — character id paired with `EXTRA_ACTIONS` when the follow-up action must be performed by a specific character and Pass is forbidden. Set alongside `EXTRA_ACTIONS = 1`; cleared when the turn actually ends (next player). Framework helpers on `Game.php` + enforcement in `ArgumentsTrait`, `FrameworkActionsTrait`, `Theah`. Pattern A.2 reference: `_03032`.
- `$character->canChallenge(): bool` — currently `return $this->isControlled();` only. It does **not** check Engaged. Layer `! $c->Engaged` yourself when the text imposes an engage cost. For Influence challenges also layer `! $c->DashedInfluence` (`Action_01033`, `Action_03057`).
- `$character->ModifiedInfluence` / `ModifiedFinesse` / `ModifiedCombat` / `ModifiedResolve` — live stats.
- `$this->getInjectCode(): string` — inline-styled card name for notifications.
- `$theah->canLocationBeClaimedBy(int $playerId, string $location): bool` — central claim gate (reads `CityLocation->CanBeClaimed`; Leshiye / Indomitable Will flip it off). Use at availability **and** emit sites for effects whose *whole* point is the claim. For Pattern A.5 refuse→claim, gate the **emit** only — do not grey the Action when claim is currently illegal (the challenge still plays). `$playerId` is reserved for future per-player rules — still pass the claimer's id.
- `$game->getControllerForLocation(string $location): int` — claim-control owner (`0` = uncontrolled). Distinct from "enemy character present."

Event factories you'll likely need:
- `createTransitionEvent($playerId, $sourceId, string $internalId, ?int $abilityId = null)` — move into a sub-state via the `*_EVENTS` transitions table.
- `createActionResolvedEvent($playerId, $actionId)` — fire when the Action's effect is fully resolved. NOT needed for challenge-issuing actions (the challenge resolution flow fires it).
- `createLocationClaimedEvent(int $playerId, ?int $performerId, string $location)` — sets location controller to `$playerId`. For Pattern A.4 opponent-claim, pass the **target's** ControllerId / Id, not the active player. For Pattern A.5 refuse→claim, pass the **challenger's** ControllerId / Id from `$event->challengerId`.
- `createCardDrawnEvent($playerId, string $reason)` — draw one card.
- `createGainLethalEvent($actorId, Theah $theah)` — grant Lethal in a duel round.
- `createReactionTransitionEvent($playerId, $sourceId, $reactionId)` — move into the reaction's player-button state.
- `createCardEngagedEvent($playerId, $cardId, $sourceId = 0, $abilityId = "")` vs `createCardEngardedEvent($playerId, $cardId, $sourceId = 0, $abilityId = "")` — **NOT synonyms.** In this game's vocabulary `Engaged = true` means "committed / has acted"; `Engaged = false` means "en garde / ready". `createCardEngagedEvent` sets `Engaged = true`; `createCardEngardedEvent` clears it back to `false`. When the printed text uses **"en garde" as a verb** ("En garde target character"), you want `createCardEngardedEvent` (clears the flag); valid targets are characters whose `Engaged == true` (Action_01081's `isValidTargetForAbility` returns "Character is already En Garded" when `!Engaged`). When the text says "engage" you want `createCardEngagedEvent` and valid targets are `Engaged == false`. Read each one literally — they're opposite operations.

Targeted-batch deletion helpers (Pattern D.3 — see the producer side in `_01117`, `_01062`, `_01150` for the canonical "queue Moving + Add + Removed with shared batchId" idiom):
- `$theah->deleteRenownAddedToLocationEventsByBatchId(int $batchId)` / `deleteRenownRemovedFromLocationEventsByBatchId(int $batchId)` — pass-throughs to `DB` helpers that anchor on `'%EventRenown<X>%'` AND `'%batchId";i:{N};%'` (note trailing `;`). Prefer these over `deleteEventBatch($batchId)` when you want to cancel only the state-mutating add/remove events, not every batch member.

`queueEvent` vs `stackEvent` rule of thumb:
- `queueEvent` → priority = the event's own `priority` field (defaults to `MEDIUM_PRIORITY = 3`). The event runs after all currently-pending events with lower-priority numbers (higher actual priority).
- `stackEvent` → priority = `min(current event_priorities) - 1`. Pre-empts every currently-pending event.
- `getNextEvent` orders by `event_priority` ASC — **lower number dequeues first**.
- If you need your reaction transition / pay events / cancel handler to run *before* an existing high-priority batch in the queue, `stackEvent` every step. Mixing `queueEvent` and `stackEvent` is the standard footgun behind "my cancel doesn't cancel anything" — by the time `EventRiskReactionTriggered` fires, the high-priority events have already mutated state.

