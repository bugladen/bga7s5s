<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01101 extends Technique
{
    private bool $IsActivated;
    
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("-1 Parry");
        $this->IsActivated = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;
        
        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        
        return $inDuel;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->IsActivated = true;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->parry -= 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] subtracts 1 Parry."), $owner->getInjectCode(), $this->Name);
        }

        if ($event instanceof EventDuelEndOfRound && $this->IsActivated)
        {
            $owningCharacter = $this->getOwningCharacter($event->theah);
            if ($owningCharacter->Id != $event->actorId)
            {
                $this->IsActivated = false;
                $owner = $this->getOwningCard($event->theah);
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelEnd && $this->IsActivated)
        {
            $this->IsActivated = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, Array &$explanations): int
    {
        $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);
        if ($this->IsActivated)
        {
            $owner = $this->getOwningCard($theah);
            $count -= 1;
            $explanations[] = sprintf($theah->game->translate("%s: -1."), $owner->getInjectCode());
        }
        return $count;
    }

}