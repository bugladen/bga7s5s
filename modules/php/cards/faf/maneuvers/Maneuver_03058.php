<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03058 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Parry and +1 Thrust per Opposing Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // Gambling Maneuver: actor must have gambled for their combat card this round.
        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            if ($actor === null)
            {
                return;
            }

            $count = $this->countOpposingCharactersAtLocation($event->theah, $actor);
            if ($count == 0)
            {
                return;
            }

            $owner = $this->getOwningCard($event->theah);
            $event->parry += $count;
            $event->thrust += $count;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s adds %d Parry and %d Thrust (+1 each for each opposing character at this location)."),
                $owner->getInjectCode(),
                $count,
                $count
            );
        }
    }

    private function countOpposingCharactersAtLocation(Theah $theah, Character $actor): int
    {
        $characters = $theah->getOpposingCharactersAtLocation($actor->Location, $actor->ControllerId);
        $characters = array_filter($characters, fn(Character $c) => $c->ControllerId != 0);
        return count($characters);
    }
}
