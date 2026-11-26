<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Customs Inspection: Academic Action - Target an opposing engaged character . Their
// controller reveals four cards from hand. Choose a revealed card and sink it.
//
// Flow (3 GameStates, ids 405026 / 4050262 / 4050263):
//   05RedTape    pick opposing engaged character (board highlight)
//   05RedTape_2  after CharacterTargeted survives: hand owner picks four to reveal
//                (auto-reveal when hand has ≤4; skip straight to _3)
//   05RedTape_3  owning player chooseList among revealed → sink
class Action_05RedTape extends RiskAction implements IAbilityThatTargetsCharacters
{
    /** @var list<int> Hand-owner-chosen revealed card ids pending the sink chooser. */
    public array $revealedCardIds = [];

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Target Opposing Engaged; Reveal Four; Sink One";
        $this->RequiresPerformerSelected = true;
    }

    /**
     * Opposing engaged characters at the performer's location whose controller has ≥1 card in hand.
     *
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        return array_values(array_filter(
            $opposing,
            function (Character $character) use ($theah) {
                if (! $character->Engaged)
                {
                    return false;
                }

                $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $character->ControllerId);
                return count($hand) > 0;
            }
        ));
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $owner = $this->getOwningCard($theah);

        // WHY: Academic Action — Academic is a mechanical performer-trait gate (not Sorcerer).
        // RiskAction (not City) — home performers stay eligible; filter from parent, not city-only.
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Academic", $owner)
                && count($this->getValidTargets($theah, $performer)) > 0
        ));
    }

    /**
     * Public reveal notifies + sticky ids for the sink chooser.
     *
     * @param list<int> $cardIds
     */
    private function revealCards(Game $game, Theah $theah, array $cardIds, int $handPlayerId): void
    {
        $owner = $this->getOwningCard($theah);
        $this->revealedCardIds = [];

        foreach ($cardIds as $cardId)
        {
            $card = $game->getCardObjectFromDb((int)$cardId);
            if ($card === null)
            {
                continue;
            }

            $theah->addCardToWorld($card);

            $game->notify->all("message",
                '${card_inject_code} reveals ${picked_card} from <strong>${player_name}</strong>\'s hand.',
                [
                    "card_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($handPlayerId),
                    "picked_card" => $card->getInjectCode(),
                    "card" => $card->getPropertyArray($game),
                ]);

            $this->revealedCardIds[] = $card->Id;
        }

        $owner->IsUpdated = true;
        $game->globals->set(Game::REVEALED_CARDS, json_encode($this->revealedCardIds));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, "Performer not found"];
        }

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, "Target must be controlled by an opponent."];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, "Target must be at your performer's location."];
        }

        if (! $character->Engaged)
        {
            return [false, "Target must be engaged."];
        }

        $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $character->ControllerId);
        if (count($hand) == 0)
        {
            return [false, "Target's controller has no cards in hand."];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $this->revealedCardIds = [];
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "05RedTape", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Hand-owner reveal chooser (or auto-reveal) only when targeting survives.
        // UL / Terraria / Maryam set canceled=true on EventCharacterTargeted.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $target = $event->theah->getCharacterById($event->targetId);

            if ($owner === null || $target === null)
            {
                return;
            }

            $hand = array_values($event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $target->ControllerId));
            $handCount = count($hand);

            if ($handCount == 0)
            {
                // WHY: Hand may empty between chooser grey and resolve (intervening discards).
                $game->notify->all("message", '${card_inject_code}: ${player_name}\'s hand is empty — no cards to reveal.', [
                    "card_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($target->ControllerId),
                ]);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $event->theah->queueEvent($actionResolvedEvent);
                $owner->IsUpdated = true;
                return;
            }

            // WHY: Print "reveals four" — when ≤4, no choice; reveal everything and open sink.
            // Hand owner only chooses when they have more than four.
            if ($handCount <= 4)
            {
                $this->revealCards(
                    $game,
                    $event->theah,
                    array_map(fn($card) => $card->Id, $hand),
                    $target->ControllerId
                );

                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05RedTape_3", $this->Id);
                $event->theah->queueEvent($transition);
                return;
            }

            // WHY: createTransitionEvent sets active = hand owner (Quack / Giacinto shape).
            // Local nextState cannot change the active player to the opponent.
            $transition = EventFactory::createTransitionEvent($target->ControllerId, $owner->Id, "05RedTape_2", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_2)
        {
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            $handCount = 0;
            if ($target !== null)
            {
                $handCount = count($game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $target->ControllerId));
            }

            $args["performerId"] = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $args["characterId"] = $targetId;
            // WHY: Exactly four when hand > 4 (short hands auto-reveal and skip this state).
            $args["cardsToReveal"] = min(4, $handCount);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_3)
        {
            $revealedIds = $this->revealedCardIds;
            if (count($revealedIds) === 0)
            {
                $decoded = json_decode($game->globals->get(Game::REVEALED_CARDS, '[]'), true);
                if (is_array($decoded))
                {
                    $revealedIds = array_values($decoded);
                }
            }

            $cards = [];
            foreach ($revealedIds as $cardId)
            {
                $card = $game->getCardObjectFromDb((int)$cardId);
                if ($card !== null)
                {
                    $cards[] = $card->getPropertyArray($game);
                }
            }
            $args["cards"] = $cards;

            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target !== null)
            {
                $args["playerName"] = $game->getPlayerNameById($target->ControllerId);
            }

            $args["performerId"] = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $args["characterId"] = $targetId;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException("Character not found");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);
            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            $game->notify->all("message", '${player_name} uses ${card_inject_code} targeting ${character_inject_code}.', [
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code" => $owner->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here. Reveal + sink chooser are queued from handleEvent
            // only when EventCharacterTargeted is not canceled (Teens / 04040 / Dabney).
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $character->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_3)
        {
            $revealedIds = $this->revealedCardIds;
            if (count($revealedIds) === 0)
            {
                $decoded = json_decode($game->globals->get(Game::REVEALED_CARDS, '[]'), true);
                if (is_array($decoded))
                {
                    $revealedIds = array_values($decoded);
                }
            }

            if (! in_array($id, $revealedIds, true))
            {
                throw new UserException("You must sink one of the revealed cards.");
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card === null)
            {
                throw new UserException("Card not found");
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new UserException("Card is no longer in hand.");
            }

            $owner = $this->getOwningCard($game->theah);
            $handPlayerId = $card->ControllerId;
            $deckName = $game->getPlayerFactionDeckName($card->OwnerId);

            // WHY: Reaction_03006 hand-sink path — insertCard alone leaves Card->Location=Hand
            // while card_location becomes Faction-*. Also fire cardRemovedFromHand so the
            // hand UI updates (EventCardAddedToFactionDeck does not).
            $game->insertCardOnPlayerFactionDeckExtreme($id, $card->OwnerId, false);
            $card->Location = $deckName;
            $game->updateCardObjectInDb($card);

            $deck = $game->getGameDeckObject();
            $game->notify->player($handPlayerId, "cardRemovedFromHand", 'Private: ${card_inject_code}: ${picked_card} was sunk from your hand.', [
                "card_inject_code" => $owner->getInjectCode(),
                "picked_card" => $card->getInjectCode(),
                "playerId" => $handPlayerId,
                "cardId" => $id,
                "handCount" => count($deck->getPlayerHand($handPlayerId)),
            ]);

            $game->notify->all("message", '${card_inject_code}: ${player_name} sinks ${picked_card} from ${opponent_name}\'s hand.', [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "picked_card" => $card->getInjectCode(),
                "opponent_name" => $game->getPlayerNameById($handPlayerId),
                "card" => $card->getPropertyArray($game),
            ]);

            $this->revealedCardIds = [];
            $owner->IsUpdated = true;
            $game->globals->delete(Game::REVEALED_CARDS);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cardChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($owner === null || $target === null)
            {
                throw new UserException("Target not found");
            }

            $playerId = (int)$game->getActivePlayerId();
            if ($playerId !== $target->ControllerId)
            {
                throw new UserException("It is not your turn to reveal.");
            }

            $needed = 4;
            if (count($ids) !== $needed)
            {
                throw new UserException("You must reveal exactly four cards from your hand.");
            }

            $uniqueIds = array_values(array_unique(array_map('intval', $ids)));
            if (count($uniqueIds) !== $needed)
            {
                throw new UserException("You must reveal four different cards.");
            }

            foreach ($uniqueIds as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card === null)
                {
                    throw new UserException("Card not found");
                }

                if ($card->Location != Game::LOCATION_HAND || $card->ControllerId != $playerId)
                {
                    throw new UserException("Card is not in your hand.");
                }
            }

            $this->revealCards($game, $game->theah, $uniqueIds, $playerId);

            // WHY: Sink chooser returns to the Action owning player (not the hand owner).
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05RedTape_3", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("cardsRevealed");
        }
    }
}
