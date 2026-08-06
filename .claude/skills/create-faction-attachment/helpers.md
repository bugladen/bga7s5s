> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Cross-Cutting Helpers

- `$theah->getCharacterById(int $id): ?Character` — hydrate a character by id.
- `$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): Character[]` — every Character at a location. Filter by `ControllerId` for friend/foe.
- `$theah->getAvailableAttachmentsAtLocation(string $location): Attachment[]` — unattached attachments sitting at a location ("available"). Pair with equipped scans when text says "available or equipped."
- `$theah->getAttachmentById(int $id): ?Attachment` — hydrate an equipped (or available) attachment.
- `$theah->getLeaderByPlayerId(int $playerId): ?Leader` — get a player's Leader (returns null if destroyed).
- `$theah->getCardObjectsAtLocation(string $location, int $playerId = 0): Card[]` — all cards in a generic location (`Game::LOCATION_HAND`, `Game::LOCATION_PLAYER_HOME`, etc.). For hand, pass the player id.
- `$theah->locationInCity(string $location): bool` / `$theah->cardInCity(Card $card): bool` — canonical City checks. **Required** for attachment City Actions (no `AttachmentCityAction` base).
- `$theah->getCityLocations(): CityLocation[]` — all five city slots; map `->Name` when building destination lists.
- `$game->getCardObjectFromDb(int $id): ?Card` — hydrate any card from db (works even if it's not in `Theah::$cards`).
- `$game->getGameDeckObject(int $playerId = 0): Deck` — get a player's deck wrapper. `getCardsInLocation(getPlayerDiscardDeckName($playerId))` queries discard; `getPlayerHand($playerId)` queries hand.
- `$game->getPlayerFactionDeckName(int $playerId): string` — the deck-table location string for a player's faction deck.
- `$game->getPlayerNameById(int $playerId): string` — use this instead of deprecated `getActivePlayerName()`.
- `$card->hasTrait(string $trait): bool` — check a trait.
- `$character->addTrait(Game $game, string $trait)` / `$character->removeTrait(Game $game, string $trait)` — for passive trait grants.
- `$card->addCondition($condition)` / `hasCondition($condition)` / `removeCondition($condition)` — lasting stamped state (Harpoon remainder-of-duel, Lodestone/Shackles while-equipped, Soline, Indomitable Will). Define the string on `Game` as `final const`. JS constant must match exactly.
- `$game->updateCardObjectInDb($card)` — flush condition / property stamps when the next event rebuild must see them (do not rely on `IsUpdated` alone for mid-resolve stamps).
- `$game->characterIsInDiscardOrLocker(Character $character): bool` — skip restoring duel-end conditions on already-removed characters.
- `$theah->getDuelRoundActor()` / `getDuelRoundOpponent()` — current duel **round** actor/opponent for Gambling Techniques / remainder-of-duel effects.
- `$event->actorId` on `EventDuelEndOfRound` — gate "at the end of **your** round" to `$event->actorId == $owningCharacter->Id` (`Technique_04016`).
- `$theah->getDuelChallengerId()` / `getDuelDefenderId()` / `getDuelOpponentId($participantId)` — duel **roster** ids for Pattern B''' "is this character in the duel / who is their adversary" gates (`_04006`). Prefer these over round-actor helpers when the bonus is participant-scoped, not round-actor-scoped.
- `$theah->getNumberOfGambleCardsToReveal(Character $actor): array` — returns `[$count, $explanationsHtml]`; base count is **2** plus every card's `getNumberOfGambleCardsToReveal` override. Attachments that add reveals implement the per-card override (Gallegos `_01101`, Assassin's Garb `_04006`).
- `$theah->swapParticipantsInDuel($duelId, $round, $oldId, $newId)` — mid-duel participant replace. Harpoon-style "cannot be swapped" must throw here *before* DB mutate.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${attachment_inject_code}` placeholder).
- **Attachment `ControllerId` after equip:** `EventHub` sets it to the **equipping player**. For `CanEquipToOpponents`, that is *not* the equipped character's controller — use `$attachedTo->ControllerId` for the victim.
- **"After a \<Trait\> equips this card"** ≠ **"May only equip to \<Trait\>"**. Former = Reaction offer gate on the host's traits (`Reaction_04016`). Latter = Pattern A `canAttachTo` + `eventCheck(Equipping)`.

**`EventAttachmentUnequipped` + `AttachedToId`:** EventHub clears `$attachment->AttachedToId = 0` **before** card `handleEvent` (event does not set `runEventHubAfterCards`). Any unequip cleanup that needs the former host must use `$event->characterId` (Pattern B trait remove, B'' condition clear, B''' bonus undo — `_04006`).

**`EventCardMoving` opponent detection:** use `$event->sourceId` → source card `ControllerId`, **not** `$event->initiatingPlayerId`. WHY: `Maneuver_01033` sets initiatingPlayerId to the victim. Own abilities pass the owner's card as `sourceId` and must still be allowed (Lodestone City Action).

**`CanEquipToOpponents` + "your performer":** `CHOSEN_PERFORMER` is the equip target. Resolve "less Stat than your performer" as a same-location ally you control with greater `Modified*` (`_03066`). See Pattern A.

Event factories you'll likely need:
- `createAttachmentUnequippedEvent($playerId, $characterId, $attachmentId)`
- `createCardRemovedFromPlayEvent($playerId, $cardId, $toLocation, $hidden = false)` — first step of "Sink this card" UI cleanup after unequip
- `createCardAddedToFactionDeckEvent($playerId, $cardId, $onTop)` — `$onTop = false` sinks to bottom; use `$attachment->OwnerId` for the playerId
- `createCardDiscardedFromPlayEvent($playerId, $cardId, $location, $sourceId, $asEffect)`
- `createCardEngagedEvent($playerId, $cardId, $sourceId, $abilityId)` — "Engage this card" on an attachment Technique: `$cardId` = attachment id
- `createCardMovingEvent($playerId, $cardId, $from, $to, $engage = true, $sourceId = 0, $abilityId = '')` — default `$engage = true`; pass `false` when move is an effect and engage/sink was a separate cost. `$unstoppable = true` bypasses Harpoon-style move blocks (Lodestone Home-from-opponent gate does not check unstoppable — printed text is ability-scoped).
- `createCharacterFinesseModifedEvent($playerId, $characterId, $old, $new, $reason)` — note the historical typo `Modifed` in the factory name. Siblings: `createCharacterCombatModifiedEvent`, `createCharacterInfluenceModifiedEvent`.
- `createCharacterBeingHealedEvent($characterId, $sourceId, $wounds, $reason, $abilityId = '')` — heal Reaction / Action (`Reaction_04016`, `Reaction_03027a`)
- `createThreatModifiedEvent($challengerThreat, $defenderThreat, …)` — "each participant gains a threat" = `(1, 1)` (`Technique_04016`, `Reaction_02039`)
- `createTransitionEvent($playerId, $sourceId, $transition, $abilityId)` — for attachment Actions, `$sourceId` is usually the **attachment** id
- `createCharacterBeingWoundedEvent($characterId, $sourceId, $wounds, $reason, $abilityId = '')`
- `createReactionTransitionEvent($playerId, $sourceId, $reactionId)`
- `createActionResolvedEvent($playerId)`
- `createSorcererAbilityStartEvent($playerId, $sourceId, $abilityId, $performerId, $targetId = 0, $targetLocation = '')`
- `createSorcererAbilityPlayedEvent($playerId, $sourceId, $abilityId, $performerId, $targetId = 0, $targetLocation = '')`
