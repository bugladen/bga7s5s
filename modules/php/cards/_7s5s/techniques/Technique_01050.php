<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01050 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("-1 Thrust, Wound the Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        $thrust = $theah->getCurrentRoundThrust();
        if ($thrust < 1)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust -= 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] subtracts 1 Thrust."), $this->getOwningCard($event->theah)->getInjectCode(), $this->Name);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($event->theah->getDuelRoundOpponent()->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);
        }
    }
}