<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_02029 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte per Diplomat at Location");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $diplomats = $event->theah->getCharactersAtLocationByPlayerId($actor->Location, $owner->ControllerId);
            $diplomats = array_filter($diplomats, fn($c) => $c->hasTrait("Diplomat"));
            $riposteBonus = count($diplomats);

            if ($riposteBonus > 0)
            {
                $event->riposte += $riposteBonus;
                $event->explanations[] = sprintf($event->theah->game->translate("%s: Maneuver [%s] adds +%d Riposte."), $owner->getInjectCode(), $this->Name, $riposteBonus);
            }
        }
    }
}
