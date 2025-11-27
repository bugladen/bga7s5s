<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01128 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Parry and +1 Thrust");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL);
        if (! $inDuel)
        {
            return false;
        }        

        $actor = $theah->getDuelRoundActor();
        if ($actor->ModifiedFinesse < 2 || $actor->ModifiedCombat < 3)
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
            $actor = $event->theah->getDuelRoundActor();
            if ($actor->ModifiedFinesse >= 2 && $actor->ModifiedCombat >= 3)
            {
                $event->parry += 1;
                $event->thrust += 1;
                $owner = $this->getOwningCard($event->theah);
                $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds +1 Parry and +1 Thrust."), $owner->getInjectCode(), $this->Name);
            }
        }
    }
}