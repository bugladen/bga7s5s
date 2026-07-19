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

class Maneuver_01164 extends Maneuver
{
    private int $MoveCharacter = 0;
    private string $MoveLocation = "";

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move to Adjacent Location");
        $this->MoveCharacter = 0;
        $this->MoveLocation = "";
    }

    // WHY: Same shape as Technique_01036 — keep the maneuver button visible when
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
                throw new UserException($event->theah->game->translate("This character is Harpooned and cannot move for the remainder of the duel."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01164", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->MoveCharacter = 0;
            $this->MoveLocation = "";
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound && $this->MoveCharacter != 0)
        {
            $game = $event->theah->game;
            $character = $game->theah->getCharacterById($this->MoveCharacter);

            if (! $game->characterIsInDiscardOrLocker($character))
            {
                $owner = $this->getOwningCard($event->theah);
                $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, $this->MoveLocation, $engage = false, $owner->Id, $this->Id);
                $event->theah->queueEvent($moveEvent);
            }

            $this->MoveCharacter = 0;
            $this->MoveLocation = "";
            $character->IsUpdated = true;
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01164)
        {
            $actor = $game->theah->getDuelRoundActor();
            $args["locationIds"] = $game->theah->getAdjacentCityLocations($actor->Location, $includeHome = false);
        }

        return $args;
    }

    public function actFromManeuverWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromManeuverWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01164)
        {
            $location = $ids[0];
            $actor = $game->theah->getDuelRoundActor();
    
            $locations = $game->theah->getAdjacentCityLocations($actor->Location, $includeHome = false);
            if (! in_array($location, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate('Location is not adjacent to %s.'), $actor->Name));
            }

            $owner = $this->getOwningCard($game->theah);
            $this->MoveCharacter = $actor->Id;
            $this->MoveLocation = $location;
            $game->updateCardObjectInDb($owner);            

            $game->gamestate->nextState();
        }
    }
}