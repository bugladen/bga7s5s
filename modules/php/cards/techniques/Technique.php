<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Technique implements ICardAbility
{
    use CardAbilityTrait;

    public bool $ResetOnDuelEnd;
    public bool $ResetOnDayEnd;

    public function __construct()
    {
        $this->initializeAbility();
        $this->ResetOnDuelEnd = true;
        $this->ResetOnDayEnd = false;
    }

    public function eventCheck(Event $event) {}

    public function handleEvent(Event $event)
    {
        if ($event instanceof EventTechniqueActivated && $event->techniqueId == $this->Id)
        {
            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventDuskEndOfDay && $this->ResetOnDayEnd)
        {
            $this->setUsed($event->theah, false);
        }

        if ($event instanceof EventDuelEnd && $this->ResetOnDuelEnd)
        {
            $this->setUsed($event->theah, false);
        }
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        return true;
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array 
    {
        return [];
    }

    public function actFromTechniquePass(Game $game, int $state): void { }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void  { }
    
    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void  { }

    public function stateFromTechnique(Game $game, int $state, string $stateName): void { }

}