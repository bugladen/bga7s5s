<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01110 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        $adversary = $theah->getCharacterById($adversaryId);
        if ($theah->game->characterIsInDiscardOrLocker($adversary))
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
            $adversaryId = $event->theah->getDuelOpponentId($actor->Id);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversaryId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);

            $actor = $event->theah->getDuelRoundActor();
            if ($actor->ModifiedCombat >= 3)
            {
                $adversary = $event->theah->getCharacterById($adversaryId);
                $transitionEvent = EventFactory::createTransitionEvent($adversary->ControllerId, $owner->Id, "01110", $this->Id);
                $event->theah->queueEvent($transitionEvent);
            }
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01110)
        {
            $owner = $this->getOwningCard($game->theah);
            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $adversary = $game->theah->getCharacterById($adversaryId);

            if ($id == 1)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversaryId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

                $game->notify->all("message", clienttranslate('${player_name} has chosen to take another wound.'), [
                    "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                ]);
            }

            if ($id == 2)
            {
                $location = $adversary->Location;

                $locationEvent = EventFactory::createLocationBecomesUncontrolledEvent($owner->ControllerId, $location);
                $game->theah->queueEvent($locationEvent);

                $game->notify->all("message", clienttranslate('${player_name} has chosen to make ${location_name} uncontrolled.'), [
                    "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                    "location_name" => $location,
                ]);
            }

        }

        $game->gamestate->nextState();
    }
}