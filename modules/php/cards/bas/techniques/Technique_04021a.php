<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04021a extends Technique
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Copy a Technique on your other Musketeer");
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

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $owner === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return count($this->getAvailableTechniques($theah)) > 0;
    }

    /**
     * Techniques printed on other controlled Musketeers at Aimée's location —
     * NOT attachment techniques (parenthetical). Skip isAvailableToPlayer: those
     * gates assume the Musketeer is the duel actor; Aimée is.
     *
     * @return Technique[]
     */
    private function getAvailableTechniques(Theah $theah): array
    {
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return [];
        }

        $techniquesArray = [];
        $characters = $theah->getCharactersAtLocation($owner->Location);
        foreach ($characters as $character)
        {
            if ($character->Id == $owner->Id)
            {
                continue;
            }
            if ($character->ControllerId != $owner->ControllerId)
            {
                continue;
            }
            if (! $character->hasTrait("Musketeer"))
            {
                continue;
            }
            if (! ($character instanceof IHasTechniques))
            {
                continue;
            }

            foreach ($character->getTechniques() as $technique)
            {
                if ($technique->ClassId === 'Technique_04021a' || $technique->IsTemporaryCopy)
                {
                    continue;
                }

                $techniquesArray[] = $technique;
            }
        }

        return $techniquesArray;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "04021", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // EventTechniqueCanceled handler not needed
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04021)
        {
            $techniquesArray = $this->getAvailableTechniques($game->theah);
            $args['techniques'] = array_map(fn($t) => $t->getPropertyArray($game), $techniquesArray);
        }

        return $args;
    }

    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04021)
        {
            $id = $ids[0];
            $technique = $game->theah->getTechniqueById($id);
            if ($technique === null)
            {
                throw new UserException($game->translate("Technique not found"));
            }

            $availableIds = array_map(fn($t) => $t->Id, $this->getAvailableTechniques($game->theah));
            if (! in_array($technique->Id, $availableIds, true))
            {
                throw new UserException($game->translate("That Technique is not available to copy"));
            }

            // Belt-and-suspenders vs attachment techniques (parenthetical forbids them).
            if ($technique->getOwningAttachment($game->theah) !== null)
            {
                throw new UserException($game->translate("Cannot copy a Technique on an attachment"));
            }

            $sourceCharacter = $technique->getOwningCharacter($game->theah);
            if ($sourceCharacter === null
                || ! $sourceCharacter->hasTrait("Musketeer")
                || $sourceCharacter->Id == $this->getOwningCharacter($game->theah)->Id)
            {
                throw new UserException($game->translate("Technique must be on your other Musketeer"));
            }

            $actor = $game->theah->getDuelRoundActor();
            $owner = $this->getOwningCard($game->theah);

            $copy = clone $technique;
            $copy->setOwnerId($actor->Id);
            $copy->Id = $actor->Id . "_copy_" . $copy->ClassId;
            $copy->IsTemporaryCopy = true;
            $copy->Used = false;

            if ($actor instanceof IHasTechniques)
            {
                $actor->addTechnique($copy, $game, $notify = false);
            }

            $owner->IsUpdated = true;

            $game->globals->set(Game::CHOSEN_TECHNIQUE, $copy->Id);
            $game->globals->set(Game::CHOSEN_TECHNIQUE_IS_MAIN, false);
            $game->globals->set(Game::TRANSITION_INTERNAL_ID, $copy->Id);

            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);

            $activateEvent = EventFactory::createTechniqueActivatedEvent($actor->ControllerId, $owner->Id, $copy->Id, $copied = true);
            $game->theah->eventCheck($activateEvent);
            $game->theah->queueEvent($activateEvent);

            $resolveEvent = EventFactory::createResolveTechniqueEvent($actor->ControllerId, $actor->Id, $adversaryId, $copy->Id);
            $game->theah->eventCheck($resolveEvent);
            $game->theah->queueEvent($resolveEvent);

            $threatEvent = EventFactory::createDuelCalculateTechniqueValuesEvent($actor->Id, $adversaryId, $copy->Id);
            $game->theah->eventCheck($threatEvent);
            $game->theah->queueEvent($threatEvent);

            $game->gamestate->nextState("cardChosen");
        }
    }
}
