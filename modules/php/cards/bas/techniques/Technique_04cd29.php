<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04cd29 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound Adversary with Lower Finesse");
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

        // Gambling Technique: actor must have gambled for their combat card this round.
        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null)
        {
            return false;
        }

        // "If the adversary has lower [Finesse] than Tijani"
        if ($adversary->ModifiedFinesse >= $owner->ModifiedFinesse)
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
            $adversary = $event->theah->getDuelRoundOpponent();

            if ($adversary !== null && $adversary->ModifiedFinesse < $owner->ModifiedFinesse)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                    $adversary->Id,
                    $owner->Id,
                    1,
                    $owner->getInjectCode(),
                    $this->Id
                );
                $event->theah->queueEvent($woundEvent);

                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s: Technique [%s] wounds the adversary."),
                    $owner->getInjectCode(),
                    $this->Name
                );
            }

            $this->setUsed($event->theah, true);
        }
    }
}
