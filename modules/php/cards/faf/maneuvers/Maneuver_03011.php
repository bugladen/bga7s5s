<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03011 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte");
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

        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return false;
        }

        // Player must control a Thug or Bodyguard at the duel's location (the actor's location).
        $characters = $theah->getCharactersAtLocation($actor->Location);
        foreach ($characters as $character)
        {
            if ($character->ControllerId != $playerId) continue;
            if ($character->hasTrait("Thug") || $character->hasTrait("Bodyguard"))
            {
                return true;
            }
        }
        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Riposte."), $owner->getInjectCode());
        }
    }
}
