<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class LocationAction extends Action
{
    public string $Id;
    public string $Name;
    protected array $playersUsed = [];
    protected Game $game;
    protected string $globalVariableName;


    public function __construct($playersUsed, Game $game)
    {
        parent::__construct();
        $this->playersUsed = $playersUsed;
        $this->game = $game;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (in_array($playerId, $this->playersUsed))
        {
            return false;
        }

        return true;
    }


    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        //Check to see if player has already used this Action today
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            if (in_array($event->playerId, $this->playersUsed))
            {
                throw new \BgaUserException($event->theah->game->translate("You have already used this Action today."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->playersUsed = [];
            $this->game->globals->set($this->globalVariableName, $this->playersUsed);
        }
    }

    public function setPlayerUsed(int $playerId)
    {
        $this->playersUsed[] = $playerId;
        $this->game->globals->set($this->globalVariableName, $this->playersUsed);
    }

    
    public function getPropertyArray(Game $game): array
    {
        return [
            "id" => $this->Id, 
            "name" => $game->translate($this->Name)
        ];
    }

}