<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01081 extends RiskCityAction implements IAbilityThatTargetsCards, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Characters");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        $result = parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck);
        if (!$result)
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        foreach ($characters as $character)
        {
            $charactersAtLocation = $theah->getCharactersAtLocation($character->Location);
            $charactersAtLocation = array_filter($charactersAtLocation, fn($character) => $character->ControllerId != $playerId && $character->Engaged);
            if (count($charactersAtLocation) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '01081', $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01081)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);
            $args['performerId'] = $performerId;

            $charactersAtLocation = $game->theah->getCharactersAtLocation($performer->Location);
            $charactersAtLocation = array_values(array_filter($charactersAtLocation, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId) && $character->Engaged));

            $args['characterIds'] = array_map(fn($character) => $character->Id, $charactersAtLocation);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01081)
        {
            $character = $game->theah->getCardById($id);
            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found."));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);

            if ($character->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("Character cannot be owned by you."));
            }

            if (! $character->Engaged)
            {
                throw new \BgaUserException($game->translate("Character is already En Garded."));
            }

            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createCardEngardedEvent($performer->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardEngardedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}