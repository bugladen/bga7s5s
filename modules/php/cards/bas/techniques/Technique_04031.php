<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneRiposte;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04031 extends Technique_PlusOneRiposte
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

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null && $owner->Engaged)
            {
                $lethalEvent = EventFactory::createGainLethalEvent($event->actorId, $event->theah);
                $event->theah->queueEvent($lethalEvent);
            }
        }
    }
}
