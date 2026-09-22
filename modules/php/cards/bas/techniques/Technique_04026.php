<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04026 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Engage: +1 Parry");
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

        $attachment = $this->getOwningCard($theah);
        if ($attachment === null || $attachment->Engaged)
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

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            if ($attachment === null)
            {
                return;
            }

            $engageEvent = EventFactory::createCardEngagedEvent(
                $event->playerId,
                $attachment->Id,
                $attachment->Id,
                $this->Id
            );
            $event->theah->queueEvent($engageEvent);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $event->parry += 1;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s: Technique [%s] adds 1 Parry."),
                $attachment !== null ? $attachment->getInjectCode() : $this->Name,
                $this->Name
            );
        }
    }
}
