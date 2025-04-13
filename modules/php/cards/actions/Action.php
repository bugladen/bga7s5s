<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Action 
{
    use CardAbilityTrait;

    public function __construct()
    {
        $this->initializeAbility();
    }

    public function actFromActionPass(Game $game, int $state): void { }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  { }
    
    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  { }

    public function handleEvent(Event $event)
    {
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $this->Used = true;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->Used = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }
    }

    public function eventCheck(Event $event) {}

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        return ! $this->Used;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        return [];
    }

    public function getCharactersForAction(int $playerId, Theah $theah): array
    {
        return [];
    }
}