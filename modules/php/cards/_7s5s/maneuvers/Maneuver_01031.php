<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01031 extends Maneuver
{
    public bool $IsActive = false;
    public string $DuelLocation = "";

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Thrust per Red Hand");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $characters = $theah->getCharactersAtLocation($actor->Location);
        $characters = array_filter($characters, fn($character) => $actor->ControllerId == $character->ControllerId && $character->hasTrait('Red Hand'));

        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            $this->IsActive = true;
            $this->DuelLocation = $actor->Location;
            
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            $characters = $event->theah->getCharactersAtLocation($actor->Location);
            $characters = array_filter($characters, fn($character) => $actor->ControllerId == $character->ControllerId && $character->hasTrait('Red Hand'));

            $event->thrust += count($characters);
            $event->explanations[] = $event->theah->game->translate("+1 Thrust for each Red Hand at same location");
        }

        if ($event instanceof EventDuelEndOfRound && $this->IsActive)
        {
            $this->IsActive = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;

            $characters = $event->theah->getCharactersAtLocation($this->DuelLocation);
            $characters = array_filter($characters, fn($character) => $owner->ControllerId == $character->ControllerId && $character->hasTrait('Thug'));

            if (count($characters) > 0)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01031", $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_END_OF_ROUND_01031)
        {
            $owner = $this->getOwningCard($game->theah);
            $characters = $game->theah->getCharactersAtLocation($this->DuelLocation);
            $characters = array_values(array_filter($characters, fn($character) => $owner->ControllerId == $character->ControllerId && $character->hasTrait('Thug')));

            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_END_OF_ROUND_01031)
        {
            $owner = $this->getOwningCard($game->theah);
            if ($id == 0)
            {
                $game->notifyAllPlayers("message", clienttranslate('${card_inject_code}: ${player_name} chooses not to destroy a Thug .'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getActivePlayerName(),
                ]);

                $game->gamestate->nextState();
                return;
            }

            $thug = $game->theah->getCharacterById($id);
            if ($thug == null)
            {
                throw new \BgaUserException($game->translate("Invalid target character id: %d"), $id);
            }

            if ($thug->ControllerId != $owner->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot choose a character that is not yours"));
            }

            if ($thug->Location != $this->DuelLocation)
            {
                throw new \BgaUserException($game->translate("Target character is not at the same location as Dante."));
            }

            $game->notifyAllPlayers("message", clienttranslate('${card_inject_code}: ${player_name} has chosen ${thug_inject_code} to destroy.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
                "thug_inject_code" => $thug->getInjectCode(),
            ]);

            $event = EventFactory::createCharacterDestroyedEvent($thug->ControllerId, $thug->Id, $owner->getInjectCode());
            $game->theah->queueEvent($event);

            $actor = $game->theah->getDuelRoundActor();
            $challengerId = $game->theah->getDuelChallengerId();
            $defenderId = $game->theah->getDuelDefenderId();
            $challengerThreatIsLethal = $actor->Id == $challengerId ? null : true;
            $defenderThreatIsLethal = $actor->Id == $defenderId ? null : true;
        
            $lethalEvent = EventFactory::createThreatModifiedEvent(0, 0, $challengerThreatIsLethal, $defenderThreatIsLethal);
            $game->theah->queueEvent($lethalEvent);

            $game->gamestate->nextState();
        }
    }


}
