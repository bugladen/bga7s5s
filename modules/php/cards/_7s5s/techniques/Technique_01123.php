<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01123 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Thrust or +1 Riposte");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        return $inDuel;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($actor->Wounds < $adversary->Wounds)
            {
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds +1 Riposte due to Valeri having fewer Wounds than opponent."), $this->Name);
                $event->riposte += 1;
            }
            else
            {
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds +1 Thrust due to Valeri having equal or more wounds than opponent."), $this->Name);
                $event->thrust += 1;
            }
        }
    }
}