<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02055 extends Technique
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Copy a Technique from your participant");
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

        $owner = $this->getOwningAttachment($theah);
        if ($owner === null || ! $owner->isAttached())
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->ControllerId != $playerId)
        {
            return false;
        }

        return count($this->getAvailableTechniques($theah)) > 0;
    }

    private function getAvailableTechniques(Theah $theah): array
    {
        $actor = $theah->getDuelRoundActor();
        $playerId = $actor->ControllerId;
        $techniquesArray = [];

        // Collect techniques from participant and their attachments,
        // filtering by isAvailableToPlayer but skipping self to avoid recursion
        if ($actor instanceof IHasTechniques)
        {
            foreach ($actor->getTechniques() as $t)
            {
                if ($t->Id !== $this->Id && $t->isAvailableToPlayer($playerId, $theah))
                    $techniquesArray[] = $t;
            }
        }

        foreach ($actor->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment instanceof IHasTechniques)
            {
                foreach ($attachment->getTechniques() as $t)
                {
                    if ($t->Id !== $this->Id && $t->isAvailableToPlayer($playerId, $theah))
                        $techniquesArray[] = $t;
                }
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
            $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "02055", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // EventTechniqueCanceled handler not needed
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02055)
        {
            $playerId = $game->getActivePlayerId();
            $techniquesArray = $this->getAvailableTechniques($game->theah);

            $args['techniques'] = array_map(fn($t) => $t->getPropertyArray($game), $techniquesArray);
        }

        return $args;
    }

    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02055)
        {
            $id = $ids[0];
            $owner = $this->getOwningCard($game->theah);
            $actor = $game->theah->getDuelRoundActor();
            $technique = $game->theah->getTechniqueById($id);
            $copy = clone $technique;
            $copy->setOwnerId($actor->Id);
            $copy->Id = $actor->Id . "_copy_" . $copy->ClassId;
            $copy->IsTemporaryCopy = true;

            if ($actor instanceof IHasTechniques) $actor->addTechnique($copy, $game, $notify = false);

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

            // Sink Dame of Swords: unequip, remove from UI, and put at bottom of faction deck
            $character = $this->getOwningCharacter($game->theah);
            if ($character)
            {
                $unequipEvent = EventFactory::createAttachmentUnequippedEvent($owner->ControllerId, $character->Id, $owner->Id);
                $game->theah->queueEvent($unequipEvent);

                $removedEvent = EventFactory::createCardRemovedFromPlayEvent($owner->ControllerId, $owner->Id, $owner->Location);
                $game->theah->queueEvent($removedEvent);
    
                $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($owner->OwnerId, $owner->Id, false);
                $game->theah->queueEvent($sinkEvent);
            }

            $game->gamestate->nextState("cardChosen");
        }
    }
}
