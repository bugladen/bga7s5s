<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01024 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Put Target Thug Into Play from Discard');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $discardName = $theah->game->getPlayerDiscardDeckName($playerId);
        $thugs = $theah->getCardObjectsAtLocation($discardName);
        $thugs = array_filter($thugs, fn($thug) => $thug->hasTrait('Thug'));
        if (count($thugs) == 0)
        {
            return false;
        }

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->hasTrait('Leader'));
        return count($characters) > 0;
    }
    
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01024", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01024)
        {
            $owner = $this->getOwningCard($game->theah);
            $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);
            $thugs = $game->theah->getCardObjectsAtLocation($discardName);
            $thugs = array_values(array_filter($thugs, fn($thug) => $thug->hasTrait('Thug')));

            $args["cards"] = array_map(fn($thug) => $thug->getPropertyArray($game), $thugs);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01024)
        {
            $thug = $game->theah->getCardById($id);
            if ($thug == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $owner = $this->getOwningCard($game->theah);
            $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);
            if ($thug->Location != $discardName)
            {
                throw new \BgaUserException($game->translate("Thug is not in your Discard Pile"));
            }

            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);

            $event = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($owner->ControllerId, $thug->Id);
            $game->theah->queueEvent($event);
            
            $event = EventFactory::createCharacterMusteredEvent($owner->ControllerId, $thug->Id, $leader->Location);
            $game->theah->queueEvent($event);

            $game->gamestate->nextState();
        }
    }
}