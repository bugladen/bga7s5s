<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_05Fedora extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Discard all of your participant's threat. Put Ariella into play at this location";
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

        $actor = $theah->getDuelRoundActor();
        if ($actor === null || ! $actor->hasTrait("Duelist"))
        {
            return false;
        }

        // WHY: Combat-card Maneuver — Ariella must still be on the dueling line as this
        // round's combat card. After resolve she leaves the line into play.
        // WHY no threat>0 gate: put-into-play remains useful with 0 threat (Henry adversary).
        $owner = $this->getOwningCard($theah);
        if ($owner === null || $owner->Location != Game::LOCATION_DUELING_LINE)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();

            // WHY: Printed order is Discard threat → Put into play. Queue discard first
            // so FIFO processes it before muster. "Your participant" = the duel side
            // controlled by Ariella's controller (Axelle / Andare ControllerId map).
            // Porté Travel `_01085` discards ALL threat via −getCurrentDuelThreat; we
            // only zero one side. Skip the event when threat is already 0 (no-op notify).
            $challengerId = $event->theah->getDuelChallengerId();
            $defenderId = $event->theah->getDuelDefenderId();
            $challenger = $challengerId !== null ? $event->theah->getCharacterById($challengerId) : null;
            $defender = $defenderId !== null ? $event->theah->getCharacterById($defenderId) : null;

            $challengerDelta = 0;
            $defenderDelta = 0;
            if ($challenger !== null && $challenger->ControllerId == $owner->ControllerId)
            {
                $threat = $event->theah->getCurrentDuelThreat($challenger->Id);
                if ($threat > 0)
                {
                    $challengerDelta = -$threat;
                }
            }
            else if ($defender !== null && $defender->ControllerId == $owner->ControllerId)
            {
                $threat = $event->theah->getCurrentDuelThreat($defender->Id);
                if ($threat > 0)
                {
                    $defenderDelta = -$threat;
                }
            }

            if ($challengerDelta != 0 || $defenderDelta != 0)
            {
                $threatEvent = EventFactory::createThreatModifiedEvent($challengerDelta, $defenderDelta);
                $event->theah->queueEvent($threatEvent);
            }

            // WHY: "this location" during a duel = the actor's (duel) location.
            // Muster from dueling line → city/Home location; createCharacterMusteredEvent
            // handles ControllerId, world add, and client cardMustered notify.
            $musterEvent = EventFactory::createCharacterMusteredEvent(
                $owner->ControllerId,
                $owner->Id,
                $actor->Location
            );
            $event->theah->queueEvent($musterEvent);
        }
    }
}
