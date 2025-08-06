<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01051 extends Maneuver
{
    public int $characterToPreventWoundsFrom;
    public int $CharacterCurrentlyTakingWounds;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Your Mercenary Takes Wounds This Round");
        $this->CharacterCurrentlyTakingWounds = 0;
        $this->characterToPreventWoundsFrom = 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $inDuel = $theah->game->globals->get(Game::IN_DUEL);
        if (! $inDuel)
            return false;

        $actor = $theah->getDuelRoundActor();
        $characters = $theah->getCharactersAtLocation($actor->Location);
        $characters = array_filter($characters, fn($character) => $character->hasTrait('Mercenary') && $character->ControllerId == $actor->ControllerId);

        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01051", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventDuelNewRound && $event->actorId == $this->characterToPreventWoundsFrom)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->CharacterCurrentlyTakingWounds = 0;
            $this->characterToPreventWoundsFrom = 0;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->CharacterCurrentlyTakingWounds = 0;
            $this->characterToPreventWoundsFrom = 0;
            $owner->IsUpdated = true;
        }        
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01051)
        {
            $actor = $game->theah->getDuelRoundActor();
            $characters = $game->theah->getCharactersAtLocation($actor->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->hasTrait('Mercenary') && $character->ControllerId == $actor->ControllerId));
            $characterIds = array_map(fn($character) => $character->Id, $characters);
    
            $args['characterIds'] = $characterIds;
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01051)
        {
            $actor = $game->theah->getDuelRoundActor();
            $character = $game->theah->getCharacterById($id);
            if (! $character)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($character->ControllerId != $actor->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot choose a character that is not yours"));
            }

            if (! $character->hasTrait('Mercenary'))
            {
                throw new \BgaUserException($game->translate("Character is not a Mercenary"));
            }

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has chosen ${character_inject_code} to take all wounds this round.'), [
                'player_name' => $game->getPlayerNameById($actor->ControllerId),
                'character_inject_code' => $character->getInjectCode(),
            ]);

            $this->characterToPreventWoundsFrom = $actor->Id;
            $this->CharacterCurrentlyTakingWounds = $character->Id;
            $owner = $this->getOwningCard($game->theah);
            $game->updateCardObjectInDb($owner);

            $game->gamestate->nextState();
        }
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // If activate, substitute the character that is taking wounds
        if ($event instanceof EventCharacterWounded && $event->characterId == $this->characterToPreventWoundsFrom)
        {
            $event->characterId = $this->CharacterCurrentlyTakingWounds;
        }
    }
}