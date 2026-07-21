<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03060 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Heal Two Wounds from Your Participant");
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
        if ($actor === null || $actor->Wounds <= 0)
        {
            return false;
        }

        // Player must control a Sorcerer at the duel's location (the actor's location).
        $characters = $theah->getCharactersAtLocation($actor->Location);
        foreach ($characters as $character)
        {
            if ($character->ControllerId != $playerId)
            {
                continue;
            }
            if ($character->hasTrait("Sorcerer"))
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

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();

            if ($actor === null || $actor->Wounds <= 0)
            {
                return;
            }

            $healEvent = EventFactory::createCharacterBeingHealedEvent(
                $actor->Id,
                $owner->Id,
                2,
                $owner->getInjectCode(),
                $this->Id
            );
            $event->theah->queueEvent($healEvent);
        }
    }
}
