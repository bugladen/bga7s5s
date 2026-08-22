<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04021b extends Technique_PlusOneThrust
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Thrust");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return false;
        }

        // En Garde Technique: precondition, not an Engage cost (Tijani / Desideria).
        if ($owner->Engaged)
        {
            return false;
        }

        if ($theah->game->globals->get(Game::IN_DUEL, false))
        {
            $actor = $theah->getDuelRoundActor();
            if ($actor === null || $actor->Id !== $owner->Id)
            {
                return false;
            }
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed
    }
}
