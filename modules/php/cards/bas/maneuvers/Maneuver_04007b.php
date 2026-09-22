<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_04007b extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("-1 Parry, +2 Thrust");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor !== null && $actor->hasTrait('Duelist');
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->parry -= 1;
            $event->thrust += 2;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s subtracts 1 Parry and adds 2 Thrust."),
                $owner->getInjectCode()
            );
        }
    }
}
