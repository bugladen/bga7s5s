<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
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

    // WHY: Same shape as Maneuver_01164 — keep the maneuver button visible when
    // Harpooned so the player can attempt it and see why it failed. Move is deferred to
    // EndOfRound; without this check they could lock in a location and only fail later.
    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            if ($actor !== null
                && $event->theah->game->globals->get(Game::IN_DUEL, false)
                && $actor->hasCondition(Game::HARPOON_CONDITION))
            {
                throw new UserException(sprintf($event->theah->game->translate("%s is Harpooned and cannot move for the remainder of the duel."), $actor->Name));
            }

            if ($actor !== null
                && $actor->hasCondition(Game::SHACKLES_CONDITION))
            {
                throw new UserException(sprintf($event->theah->game->translate("%s is Shackled and cannot move."), $actor->Name));
            }
        }
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

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->selectedLocation = "";
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound && $this->selectedLocation != "")
        {
            $actor = $event->theah->getDuelRoundActor();

            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if (! $game->characterIsInDiscardOrLocker($actor))
            {
                $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $actor->Id, $actor->Location, $this->selectedLocation, $engage = false, $owner->Id, $this->Id);
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