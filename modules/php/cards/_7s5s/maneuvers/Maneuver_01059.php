<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01059 extends Maneuver
{
    private string $selectedLocation;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Participant");
        $this->selectedLocation = "";
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01059", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventDuelEndOfRound && $this->selectedLocation != "")
        {
            $actor = $event->theah->getDuelRoundActor();

            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if (! $game->characterIsInDiscardOrLocker($actor))
            {
                $moveEvent = EventFactory::createCardMovedEvent($owner->ControllerId, $actor->Id, $actor->Location, $this->selectedLocation, $engage = false, $owner->Id);
                $event->theah->queueEvent($moveEvent);    
            }

            $this->selectedLocation = "";
            $owner->IsUpdated = true;
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01059)
        {
            $actor = $game->theah->getDuelRoundActor();
            $args['performerId'] = $actor->Id;            
            $args['locationIds'] = $game->theah->getAdjacentCityLocations($actor->Location, $includeHome = false);
        }

        return $args;
    }

    public function actFromManeuverWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromManeuverWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01059)
        {
            $location = $ids[0];

            $actor = $game->theah->getDuelRoundActor();
            $locations = $game->theah->getAdjacentCityLocations($actor->Location, $includeHome = false);
            if (! in_array($location, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate("Location is not adjacent to %s."), $actor->Name));
            }

            $this->selectedLocation = $location;
            $owner = $this->getOwningCard($game->theah);
            $game->updateCardObjectInDb($owner);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has chosen to move to ${location_name} at the end of the round.'), [
                "i18n" => ["location_name"],
                "player_name" => $game->getPlayerNameById($actor->ControllerId),
                "location_name" => $location
            ]);

            $game->gamestate->nextState();
        }
    }

}