<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01152a extends SchemeCityAction implements IAbilityThatTargetsCards, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Performer, En Garde Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $theah->getCharactersInCityByPlayerId($playerId);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01152a", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01152a)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
    
            $performer = $game->theah->getCharacterById($performerId);
            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_filter($characters, fn($character) => $character->Engaged);
            $args['characterIds'] = array_map(fn($character) => $character->Id, array_values($characters));
        }    

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01152a)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if (! $target->Engaged)
            {
                throw new \BgaUserException($game->translate("Character is not engaged"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($target->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the Performer"));
            }

            $scheme = $this->getOwningCard($game->theah);

            $event = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $scheme->Id, 1, $scheme->getInjectCode(), $this->Id);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardEngardedEvent($performer->ControllerId, $target->Id, $scheme->Id, $this->Id);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->announceAction($game);

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("targetChosen");
        }        
    }
}