<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01159 extends CardAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Your Performer at controlled location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->Engaged);
        foreach ($characters as $character)
        {
            $location = $theah->getCityLocation($character->Location);
            if ($location->Controller == $playerId)
            {
                return true;
            }
        }

        return false;
    }

    public function getActionFromHandDiscount(Theah $theah, Character $performer, CardAction $action): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action);

        $owner = $this->getOwningCard($theah);
        if ($action->Id == $this->Id)
        {
            $leader = $theah->getLeaderByPlayerId($performer->ControllerId);
            if ($leader->hasTrait('Diplomat') || $leader->hasTrait('Hero'))
            {
                $discount += 1;            
            }
        }

        return $discount;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->Engaged);
        foreach ($characters as $character)
        {
            $location = $theah->getCityLocation($character->Location);
            if ($location->Controller == $playerId)
            {
                return [$character];
            }
        }

        return $characters;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            
            $owner = $this->getOwningCard($event->theah);
            $engardeEvent = EventFactory::createCardEngardedEvent($owner->ControllerId, $performerId, $owner->Id);
            $event->theah->queueEvent($engardeEvent);
        }
    }
}