<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02006 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound other Character at Location. +1 Parry");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        if ($owner->Engaged)
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $charactersAtLocation = $theah->getCharactersAtLocationByPlayerId($actor->Location, $actor->ControllerId);
        $charactersAtLocation = array_filter($charactersAtLocation, fn($character) => $character->Id != $actor->Id);
        return count($charactersAtLocation) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "02006", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->parry += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 1 Parry."), $owner->getInjectCode(), $this->Name);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02006)
        {
            $actor = $game->theah->getDuelRoundActor();
            $args["performerId"] = $actor->Id;

            $charactersAtLocation = $game->theah->getCharactersAtLocationByPlayerId($actor->Location, $actor->ControllerId);
            $charactersAtLocation = array_filter($charactersAtLocation, fn($character) => $character->Id != $actor->Id);
            $args["ids"] = array_map(fn($character) => $character->Id, array_values($charactersAtLocation));
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02006)
        {
            $actor = $game->theah->getDuelRoundActor();
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            if ($character->ControllerId != $actor->ControllerId)
            {
                throw new UserException($game->translate("Character is not controlled by you."));
            }

            if ($character->Id == $actor->Id)
            {
                throw new UserException($game->translate("You must choose a different character."));
            }
            
            if ($character->Location != $actor->Location)
            {
                throw new UserException(sprintf($game->translate("Character is not at the same location as %s."), $actor->Name));
            }

            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $game->gamestate->nextState();
        }
    }
}