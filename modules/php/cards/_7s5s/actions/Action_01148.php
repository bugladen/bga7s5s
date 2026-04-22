<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01148 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard cards to Engage or Wound an Opposing Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityWithOpposingMercenaries($playerId);
        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $theah->getCharactersInCityWithOpposingMercenaries($playerId);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId === $this->Id)
        {
            $scheme = $this->getOwningCard($event->theah);
            $transistion = EventFactory::createTransitionEvent($scheme->ControllerId, $scheme->Id, "01148", $this->Id);
            $event->theah->queueEvent($transistion);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01148)
        {
            $scheme = $this->getOwningCard($game->theah);
            $args["schemeId"] = $scheme->Id;

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $opposingCharacters = $game->theah->getOpposingMercenariesAtLocation($performer->Location, $performer->ControllerId);
            $args["charactersIds"] = array_map(fn($character) => $character->Id, $opposingCharacters);
        }

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01148_3 || $state === States::HIGH_DRAMA_PLAYER_TURN_01148_4)
        {
            $scheme = $this->getOwningCard($game->theah);
            $args["schemeId"] = $scheme->Id;
            $args["performerId"] = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args["targetId"] = $game->globals->get(Game::CHOSEN_TARGET);
        }

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01148_4)
        {
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            $args["isEngaged"] = $target->Engaged;
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("Cannot manipulate a card that belongs to you")];
        }

        if ($character->Location !== $performer->Location)
        {
            return [false, $game->translate("Card must be at the same location as the performer")];
        }

        if (!$character->hasTrait("Mercenary"))
        {
            return [false, $game->translate("Target must be a Mercenary")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01148)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Invalid character selected"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }



            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);
            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);
            $this->announceAction($game);

            $game->gamestate->nextState("mercenaryChosen");
        }

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01148_3)
        {
            // Finished
            if ($id === 0)
            {
                $scheme = $this->getOwningCard($game->theah);
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($scheme->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState();
                return;
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card == null)
            {
                throw new UserException($game->translate("Invalid card selected"));
            }

            $scheme = $this->getOwningCard($game->theah);
            if ($card->ControllerId !== $scheme->ControllerId || $card->Location != Game::LOCATION_HAND)
            {
                throw new UserException($game->translate("Card is not in your hand"));
            }

            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $owner->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createTransitionEvent($scheme->ControllerId, $scheme->Id, "01148_3", $this->Id);
            $game->theah->queueEvent($event);

            $game->gamestate->nextState();
        }

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01148_4)
        {
            $scheme = $this->getOwningCard($game->theah);
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            // Engage
            if ($id === 1)
            {
                $event = EventFactory::createCardEngagedEvent($scheme->ControllerId, $targetId, $scheme->Id, $this->Id);
                $game->theah->queueEvent($event);
            }

            // Wound
            if ($id === 2)
            {
                $event = EventFactory::createCharacterBeingWoundedEvent($target->Id, $scheme->Id, 1, $scheme->getInjectCode(), $this->Id);
                $game->theah->queueEvent($event);
            }

            $event = EventFactory::createTransitionEvent($scheme->ControllerId, $scheme->Id, "01148_4", $this->Id);
            $game->theah->queueEvent($event);

            $game->gamestate->nextState();
            
        }
    }

    public function stateFromAction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromAction($game, $state, $stateName);

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01148_2)
        {
            $scheme = $this->getOwningCard($game->theah);
            $deck = $game->getGameDeckObject();
            $cards = $deck->getCardsInLocation(Game::LOCATION_HAND, $scheme->ControllerId);

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            if (count($cards) > 0 && $target->ControllerId > 0)
            {
                $game->gamestate->nextState("proceed");
                return;
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($scheme->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cancel");
        }
    }
}