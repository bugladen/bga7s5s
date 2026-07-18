> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Scheme class:   `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:         `...\cards\<expansion>\actions`
  - Reaction:       `...\cards\<expansion>\reactions`
  - State class:    `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
- **State ID convention:** `26<NNNNN>` Planning resolve; `28<NNNNN>` Planning End Forced; `40<NNNNN>` High Drama action. Don't engineer around hypothetical CD-card collisions — `2603005` / `2803041` are correct even if `_03cd05` / similar exist. (Memory feedback.)
- **Three transition tables:** resolve picks → `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS`; Forced end picks → `PLANNING_PHASE_END_EVENTS`; action picks → `HIGH_DRAMA_PLAYER_TURN_EVENTS`. Same string key (e.g. `"01098"`) may appear in more than one map — that is intentional.
- **Chosen schemes live at `LOCATION_PLAYER_HOME` until Dusk** (then Locker). Not discard after resolve. Forced/Action gates and `SchemeAction` availability rely on this.
- **"Opposing"** means BOTH different controller AND same location.
- **Traits in `TraitNames::$TraitsJson`** — add missing ones in alphabetical order.
- **Typed PHP parameters required.** Every function/method signature must declare a type for every parameter — no bare `$foo`. Use concrete types (`Card $owner`, `Character $performer`, `Game $game`, `Theah $theah`, `Event $event`, `int $cardId`, `string $reactionId`). Add the `use` import.
- **"Strega" / "Mercenary" / "Diplomat" / etc.** are **mechanical performer-trait gates**, not flavor. Enforce via `hasTrait("Strega")` on the chosen performer. They are NOT Sorcerer abilities — do NOT `implement ISorcererAbility` for them. Only the literal "Sorcerer" keyword triggers `ISorcererAbility`. They can stack ("Sorcerer Strega Reaction" is both).
- **Windows line endings:** PHP files in this repo use single CRLF (`\r\n`). Agent-written files sometimes land as `\r\r\n` (double carriage return), which displays as a blank line after every line (and doubles reported line counts). After writing scheme/action PHP, verify `doubleCR=0` with a byte scan. PowerShell string `-replace "`r`r`n"` is unreliable on some hosts — prefer a byte-list walk that collapses `13,13,10` → `13,10`. Match existing files — do not convert LF-only.
- **GameState transitions with Back:** never pair `""` with `"back"` (or any second key). Use named success transitions (`"cardDiscarded"`, `"done"`, …). Studio error if you don't: "More than one possible transition at this state".
- **Card-specific challenge sub-transitions** off `HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_CHALLENGE` use the card number string (`"03042"`), not a reusable semantic name. Same discipline as HD_EVENTS / resolve maps.
- **Challenge-type JS int:** if Refuse/Intervene UI needs the type, add the matching constant in `seventhseacityoffivesails.js`. Types with no client gate can omit it (server-only), but discard-to-refuse needs the client disable.
- **Two-location resolve JS:** faf/tac/_7s5s triple is not enough — `PlayerActions.js` `actionMap` must map `planningPhaseResolveSchemes_<NNNNN>` → `'actCityLocationsForReknownSelected'`. Without it Confirm falls through to `actFromCardWithLocations` and breaks.
- **"Spend a Renown"** without a named location is always player score (`createPlayerLosesReknownEvent`), never a location token.
- **"Unequipped"** = `count($character->Attachments) == 0` (not a trait). Re-check on trigger; JS can be ignored.
- **Ability pressure must `createActionResolvedEvent` on failure** (and on success with no legal payoff). Hub auto-resolve is only for `highDramaBasicAction`. Mirror `Action_03040` / `Action_03cd20` / `Action_03054`, not the incomplete failure path on `Action_01105`.
- **Wound-then-pressure:** always `CHOSEN_LOCATION` + `CHOSEN_PERFORMER = 0` (+ `CHOSEN_CARD` for UI) before queuing the performer wound and `"pressureLocation"`. See Pattern I.
- **Wound + move Home on the same target:** skip the Home move when `$Wounds + 1 >= ModifiedResolve` so destroy stays at the city (no locker→Home yank).
- **Printed location names drift:** cross-check JPG / Eddie corrections before shipping Renown adds (Forum vs Bazaar is a common swap).

## Cross-Cutting Helpers

- `$theah->getCityLocation(string $name): ?CityLocation` — current Renown/controller for a city location. Returns `null` for non-city locations (defensive guard).
- `$theah->getCityLocations(): array` — all city locations in play (3 in 2p, 4 in 3p, 5 in 4p).
- `$theah->cardInCity($card): bool` — true when the card is at a city location.
- `$theah->locationInCity(string $location): bool` — the canonical "City location" check (Oles Inn / Docks / Forum / Bazaar / Governor's Garden). Use this when you only have the location string in hand (e.g. a captured `$this->location` on a Reaction) rather than a Card object. The printed keyword **"City"** on a card text maps directly to this set — not Home, not Locker, not Discard.
- `$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): Character[]` — every Character at the location. Filter by `ControllerId` to split friend/foe.
- `$theah->getCharactersInPlayByPlayerId($playerId): Character[]` — "control an Academic/…" checks: loop and `hasTrait(...)`. Includes Home and Leaders.
- `$game->getCardObjectFromDb(int $id): ?Card` — hydrate a card from any location by id.
- `$game->getGameDeckObject(int $playerId): Deck` — get a player's deck wrapper. `getCardsInLocation(getPlayerDiscardDeckName($playerId))` is the discard query.
- `$game->getPlayerDiscardDeckName(int $playerId): string` — the deck-table location string for a player's discard pile.
- `$card->hasTrait(string $trait): bool` — check a trait. English strings compare directly against `clienttranslate()`-wrapped values.
- `$theah->canLocationBeClaimedBy(int $playerId, string $location): bool` — central claimability gate (flags, controllers, etc.). Use in **availability / performer filters** when Claim is the payoff so the action is never offered when unclaimable; recheck at resolve before `createLocationClaimedEvent`.
- `$game->getPlayerReknown(int $playerId): int` — player score Renown (for "Spend a Renown" costs).
- `$this->getInjectCode()` — inline-styled card name for notifications (`${scheme_inject_code}` placeholder).

Event factories you'll likely need:
- `createRenownAddedToLocationEvent($playerId, $location, $count, $reason, $isMove = false)`
- `createPlayerLosesReknownEvent($playerId, $amount)` — "Spend a Renown" (player score), not location tokens
- `createCharacterBeingWoundedEvent($characterId, $sourceId, $wounds, $reason, $abilityId = '')`
- `createCharacterBeingHealedEvent($characterId, $sourceId, $wounds, $reason, $abilityId = '')`
- `createCardDrawnEvent($playerId, $reason)` — for "draw a card" clauses.
- `createCardDiscardedFromHandEvent($ownerId, $cardId, $sourceId, $asPayment = false, $asPlayed = false, $asEffect = false)` — Forced/effect discards use `$asEffect = true`.
- `createRenownRemovedFromLocationEvent($playerId, $location, $count, $reason)`
- `createRenownMovingBetweenLocationsEvent($playerId, $from, $to, $amount, $description)` — pair with remove + add(`isMove`) under one `batchId` for UI/animation.
- `createCardRemovedFromPlayerDiscardPileEvent($playerId, $cardId)` (notification-only)
- `createCardAddedToHandEvent($playerId, $cardId)` (does the actual move)
- `createLocationClaimedEvent($playerId, ?int $performerId, $location)`
- `createPressureOccuringEvent($playerId, $performerId, $location, $pressureTypes)` — then transition `"pressureLocation"`; listen for `EventLocationPressureResult` with matching `$abilityId`
- `createCardMovingEvent($playerId, $cardId, $from, $to, $engage, $sourceId, $abilityId)` — Home moves use `Game::LOCATION_PLAYER_HOME` and usually `$engage = false`
- `createTransitionEvent($playerId, $sourceId, string $internalId)` — for moving into a sub-state.
- `createReactionTransitionEvent($playerId, $sourceId, $reactionId)` — for the reaction's transition.
