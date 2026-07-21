<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03069b extends Maneuver_03069a
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte; Swap Participant with Other Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // Gambling Maneuver
        return (bool) $theah->game->globals->get(Game::DUEL_GAMBLED, false);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s adds 1 Riposte."),
                $owner->getInjectCode()
            );
        }
    }
}
