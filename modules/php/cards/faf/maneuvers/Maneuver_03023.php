<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03023 extends Maneuver
{
    private int $ActorId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Suppress Threat Conversion");
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

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            $this->ActorId = $actor->Id;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        // Hook the character being wounded event at end of duel round.
        // If this maneuver was played and the adversary is present, prevent the wounding.
        if ($event instanceof EventCharacterBeingWounded && $this->ActorId > 0)
        {
            // Check if this is the threat-to-wound conversion at end of round
            // (indicated by sourceId being the adversary, which isn't set for direct wound events)
            $actor = $event->theah->getDuelRoundActor();
            if ($actor !== null && $event->characterId == $this->ActorId)
            {
                $adversaryId = $event->theah->getDuelOpponentId($actor->Id);
                $adversary = $event->theah->getCharacterById($adversaryId);

                // If adversary is present, suppress the wound
                if (! $event->theah->game->characterIsInDiscardOrLocker($adversary))
                {
                    $event->wounds = 0;
                }
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->ActorId = 0;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound && $this->ActorId > 0)
        {
            $this->ActorId = 0;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }
}
