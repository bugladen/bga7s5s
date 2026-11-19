<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_05Corncob extends Technique_PlusOneParry
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "+1 Parry";
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

        // WHY: Covert Technique — Covert abilities only at uncontrolled City locations (Valeri / Overcoat).
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $cityLocation = $theah->getCityLocation($owner->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
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
