<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03051 extends Technique
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Yepikhodov's attachment: Copy a Technique on it");
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
     * WHY CardNumber/ExpansionName instead of instanceof _03051: importing the
     * card class from this Technique would create a circular require (card
     * grants Technique_03051).
     */
    private function findYepikhodov(Theah $theah): ?Character
    {
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return null;
        }

        $characters = $theah->getCharactersAtLocation($owner->Location);
        foreach ($characters as $character)
        {
            if ($character->ExpansionName === 'faf'
                && $character->CardNumber === 51
                && $character->ControllerId == $owner->ControllerId
                && $character->Id != $owner->Id)
            {
                return $character;
            }
        }

        return null;
    }

    /**
     * WHY skip isAvailableToPlayer on source techniques: those techniques gate on
     * actor == Yepikhodov. The duel actor is the ally who was granted this
     * Technique, so availability would always fail. List techniques that live on
     * his unengaged attachments; the copy is resolved as the actor.
     *
     * @return Technique[]
     */
    private function getAvailableTechniques(Theah $theah): array
    {
        $yepikhodov = $this->findYepikhodov($theah);
        if ($yepikhodov === null)
        {
            return [];
        }

        $techniquesArray = [];
        foreach ($yepikhodov->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment === null || $attachment->FakeAttachment || $attachment->Engaged)
            {
                continue;
            }

            if (! ($attachment instanceof IHasTechniques))
            {
                continue;
            }

            foreach ($attachment->getTechniques() as $technique)
            {
                if ($technique->ClassId === 'Technique_03051' || $technique->IsTemporaryCopy)
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
            $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "03051", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // EventTechniqueCanceled handler not needed
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03051)
        {
            $techniquesArray = $this->getAvailableTechniques($game->theah);
            $args['techniques'] = array_map(fn($t) => $t->getPropertyArray($game), $techniquesArray);
        }

        return $args;
    }

    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03051)
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

            $attachment = $technique->getOwningAttachment($game->theah);
            if ($attachment === null || $attachment->Engaged || $attachment->FakeAttachment)
            {
                throw new UserException($game->translate("Yepikhodov's attachment must be unengaged"));
            }

            $yepikhodov = $this->findYepikhodov($game->theah);
            if ($yepikhodov === null || ! in_array($attachment->Id, $yepikhodov->Attachments))
            {
                throw new UserException($game->translate("Attachment must be equipped to Yepikhodov"));
            }

            $actor = $game->theah->getDuelRoundActor();
            $owner = $this->getOwningCard($game->theah);

            $engageEvent = EventFactory::createCardEngagedEvent($actor->ControllerId, $attachment->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

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
