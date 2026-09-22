<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04032 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    /** @var list<int> Full hand shown in the multiplayer acknowledge state. */
    public array $revealedCardIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Giacinto and Target to Adjacent City unless Opponent Discards");
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $giacinto): array
    {
        return array_values($theah->getOpposingCharactersAtLocation($giacinto->Location, $giacinto->ControllerId));
    }

    /**
     * @return list<string>
     */
    private function getAdjacentCityLocations(Theah $theah, Character $giacinto): array
    {
        return $theah->getAdjacentCityLocations($giacinto->Location, $includeHome = false);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $giacinto = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($giacinto))
        {
            return false;
        }

        // WHY En Garde City Action: ready precondition, not an Engage cost.
        if ($giacinto->Engaged)
        {
            return false;
        }

        if (count($this->getValidTargets($theah, $giacinto)) == 0)
        {
            return false;
        }

        return count($this->getAdjacentCityLocations($theah, $giacinto)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04032", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $giacinto = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04032)
        {
            $args["performerId"] = $giacinto->Id;
            $args["ids"] = array_values(array_map(
                fn(Character $character) => $character->Id,
                $this->getValidTargets($game->theah, $giacinto)
            ));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04032_2)
        {
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $args["performerId"] = $giacinto->Id;
            $args["characterId"] = $targetId;

            $target = $game->theah->getCharacterById($targetId);
            if ($target !== null)
            {
                $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $target->ControllerId);
                $args["canRevealHand"] = count($hand) > 0;
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04032_3)
        {
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $args["performerId"] = $giacinto->Id;
            $args["characterId"] = $targetId;
            $args["locationIds"] = $this->getAdjacentCityLocations($game->theah, $giacinto);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04032_4)
        {
            $revealedIds = json_decode($game->globals->get(Game::REVEALED_CARDS, '[]'), true);
            if (! is_array($revealedIds) || count($revealedIds) === 0)
            {
                $revealedIds = $this->revealedCardIds;
            }

            $cards = [];
            foreach ($revealedIds as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
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
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04032_5)
        {
            $revealedIds = json_decode($game->globals->get(Game::REVEALED_CARDS, '[]'), true);
            if (! is_array($revealedIds) || count($revealedIds) === 0)
            {
                $revealedIds = $this->revealedCardIds;
            }

            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $args["performerId"] = $giacinto->Id;
            $args["characterId"] = $targetId;
            $args["cardIds"] = array_values($revealedIds);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $giacinto = $this->getOwningCharacter($game->theah);

        if (! $character->isControlled() || $character->ControllerId == $giacinto->ControllerId)
        {
            return [false, $game->translate("You must target an opposing character.")];
        }

        if ($character->Location != $giacinto->Location)
        {
            return [false, $game->translate("Target must be at Giacinto's location.")];
        }

        return [true, ""];
    }

    /**
     * @return list<int>
     */
    private function revealHand(Game $game, Theah $theah, int $playerId): array
    {
        $owner = $this->getOwningCharacter($theah);
        $deck = $game->getGameDeckObject();
        $hand = array_values($deck->getCardsInLocation(Game::LOCATION_HAND, $playerId));
        $revealedIds = [];

        foreach ($hand as $handCard)
        {
            $card = $game->getCardObjectFromDb($handCard['id']);
            if ($card === null)
            {
                continue;
            }

            $theah->addCardToWorld($card);

            $game->notify->all("message",
                clienttranslate('${card_inject_code}: ${player_name} reveals ${picked_card} from their hand.'),
                [
                    "card_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($playerId),
                    "picked_card" => $card->getInjectCode(),
                    "card" => $card->getPropertyArray($game),
                ]);

            $revealedIds[] = $card->Id;
        }

        return $revealedIds;
    }

    public function stateFromAction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromAction($game, $state, $stateName);
    }

    public function actFromActionRevealHand(Game $game, int $state, string $stateName = ''): void
    {
        if ($state != States::HIGH_DRAMA_PLAYER_TURN_04032_2)
        {
            return;
        }

        $playerId = (int)$game->getCurrentPlayerId();
        $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
        $target = $game->theah->getCharacterById($targetId);
        if ($target === null)
        {
            throw new UserException($game->translate("Character not found"));
        }

        if ($playerId !== $target->ControllerId)
        {
            throw new UserException($game->translate("It is not your turn to respond"));
        }

        $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        if (count($hand) === 0)
        {
            throw new UserException($game->translate("You have no cards in hand to reveal"));
        }

        $owner = $this->getOwningCharacter($game->theah);

        $this->revealedCardIds = $this->revealHand($game, $game->theah, $playerId);
        $owner->IsUpdated = true;

        $game->globals->set(Game::REVEALED_CARDS, json_encode($this->revealedCardIds));

        // WHY: Active-player view for Giacinto with chooseList (not multi-ack). EventTransition
        // sets active player to Giacinto's controller so they see the revealed hand before discard.
        $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04032_4", $this->Id);
        $game->theah->queueEvent($transition);

        $game->gamestate->nextState("");
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04032)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCharacter($game->theah);
            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            $transition = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "04032_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("targetChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04032_5)
        {
            $playerId = (int)$game->getCurrentPlayerId();
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            if ($playerId !== $target->ControllerId)
            {
                throw new UserException($game->translate("It is not your turn to discard"));
            }

            $revealedIds = json_decode($game->globals->get(Game::REVEALED_CARDS, '[]'), true);
            if (! is_array($revealedIds) || ! in_array($id, $revealedIds, true))
            {
                throw new UserException($game->translate("You must discard one of the revealed cards"));
            }

            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
            $handIds = array_map(fn($card) => $card->Id, $hand);
            if (! in_array($id, $handIds, true))
            {
                throw new UserException($game->translate("Invalid card choice"));
            }

            $owner = $this->getOwningCharacter($game->theah);

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $playerId,
                $id,
                $owner->Id,
                false,
                false,
                true
            );
            $game->theah->eventCheck($discardEvent);
            $game->theah->queueEvent($discardEvent);

            $discardedCard = $game->getCardObjectFromDb($id);
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} discards ${picked_card} to prevent the move.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "picked_card" => $discardedCard !== null ? $discardedCard->getInjectCode() : "",
            ]);

            $this->revealedCardIds = [];
            $owner->IsUpdated = true;
            $game->globals->delete(Game::REVEALED_CARDS);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("");
        }
    }

    public function actFromActionPass(Game $game, int $state, string $stateName = ''): void
    {
        parent::actFromActionPass($game, $state);

        if ($state != States::HIGH_DRAMA_PLAYER_TURN_04032_2)
        {
            return;
        }

        $playerId = (int)$game->getCurrentPlayerId();
        $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
        $target = $game->theah->getCharacterById($targetId);
        if ($target === null)
        {
            throw new UserException($game->translate("Character not found"));
        }

        if ($playerId !== $target->ControllerId)
        {
            throw new UserException($game->translate("It is not your turn to respond"));
        }

        $owner = $this->getOwningCharacter($game->theah);

        $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04032_3", $this->Id);
        $game->theah->queueEvent($transition);

        // WHY: single "" transition only (02036_2 shape) — a second "pass" key causes
        // "More than one possible transition" when nextState("") runs on discard.
        $game->gamestate->nextState("");
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04032_3)
        {
            $location = $ids[0];

            if ($game->theah->getCityLocation($location) == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            $giacinto = $this->getOwningCharacter($game->theah);
            $validLocations = $this->getAdjacentCityLocations($game->theah, $giacinto);
            if (! in_array($location, $validLocations, true))
            {
                throw new UserException($game->translate("Location must be adjacent to Giacinto."));
            }

            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $batchId = $game->getNextEventBatchId();

            $moveGiacinto = EventFactory::createCardMovingEvent(
                $giacinto->ControllerId,
                $giacinto->Id,
                $giacinto->Location,
                $location,
                $engage = false,
                $giacinto->Id,
                $this->Id
            );
            $moveGiacinto->batchId = $batchId;
            $game->theah->eventCheck($moveGiacinto);
            $game->theah->queueEvent($moveGiacinto);

            $moveTarget = EventFactory::createCardMovingEvent(
                $giacinto->ControllerId,
                $target->Id,
                $target->Location,
                $location,
                $engage = false,
                $giacinto->Id,
                $this->Id
            );
            $moveTarget->batchId = $batchId;
            $game->theah->eventCheck($moveTarget);
            $game->theah->queueEvent($moveTarget);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} moves ${owner_inject_code} and ${target_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "card_inject_code" => $giacinto->getInjectCode(),
                "player_name" => $game->getPlayerNameById($giacinto->ControllerId),
                "owner_inject_code" => $giacinto->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
                "location_name" => $location,
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($giacinto->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
