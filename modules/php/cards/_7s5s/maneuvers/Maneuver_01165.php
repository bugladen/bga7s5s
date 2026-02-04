<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01165 extends Maneuver
{
    public Array $copiedTechniques = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Copy Technique from Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
            return false;

        $adversary = $theah->getDuelRoundOpponent();

        if ($theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        $techniques = $adversary instanceof IHasTechniques ? $adversary->getTechniquesAvailableToPlayer($theah->game, $playerId) : [];
        if (count($techniques) > 0)
            return true;

        foreach ($adversary->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            $techniques = $attachment instanceof IHasTechniques ? $attachment->getTechniquesAvailableToPlayer($theah->game, $playerId) : [];
            if (count($techniques) > 0)
                return true;
        }

        return false;
    }

    private function removeCopiedTechniques(Theah $theah): void
    {
        foreach ($this->copiedTechniques as $technique)
        {
            $techniqueOwner = $technique->getOwningCard($theah);
            if ($techniqueOwner instanceof IHasTechniques) $techniqueOwner->removeTechnique($technique, $theah->game, $notify = false);
            $techniqueOwner->IsUpdated = true;
        }
        $this->copiedTechniques = [];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01165", $this->Id);
            //Make sure this is the last event to run, as it is going to place us in a different state group.
            //We want to make sure that all the other events are run before this one.
            $transition->priority = Event::LOWEST_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventDuelNewRound)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->ControllerId == $event->playerId)
            {
                $this->removeCopiedTechniques($event->theah);
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->removeCopiedTechniques($event->theah);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01165)
        {
            $playerId = $game->getActivePlayerId();
            $adversary = $game->theah->getDuelRoundOpponent();

            $techniquesArray = [];
            $techniques = $adversary instanceof IHasTechniques ? $adversary->getTechniquesAvailableToPlayer($game, $playerId) : [];
            if (count($techniques) > 0)
                $techniquesArray = array_merge($techniquesArray, $techniques);
    
            foreach ($adversary->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                $techniques = $attachment instanceof IHasTechniques ? $attachment->getTechniquesAvailableToPlayer($game, $playerId) : [];
                if (count($techniques) > 0)
                    $techniquesArray = array_merge($techniquesArray, $techniques);
            }
    
            $args['techniques'] = array_values($techniquesArray);
        }

        return $args;
    }

    public function actFromManeuverWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromManeuverWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01165)
        {
            $id = $ids[0];
            $owner = $this->getOwningCard($game->theah);
            $actor = $game->theah->getDuelRoundActor();
            $technique = $game->theah->getTechniqueById($id);
            $copy = clone $technique;
            $copy->setOwnerId($actor->Id);

            if ($actor instanceof IHasTechniques) $actor->addTechnique($copy, $game, $notify = false);

            $this->copiedTechniques[] = $copy;
            $owner->IsUpdated = true;

            $game->globals->set(Game::CHOSEN_TECHNIQUE, $copy->Id);
            $game->globals->set(Game::CHOSEN_TECHNIQUE_IS_MAIN, false);
            $game->globals->set(GAME::TRANSITION_INTERNAL_ID, $copy->Id);

            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $owner = $this->getOwningCard($game->theah);

            $activateEvent = EventFactory::createTechniqueActivatedEvent($actor->ControllerId, $owner->Id, $copy->Id, $copied = true);
            $game->theah->eventCheck($activateEvent);
            $game->theah->queueEvent($activateEvent);

            $resolveEvent = EventFactory::createResolveTechniqueEvent($actor->ControllerId, $actor->Id, $adversaryId, $copy->Id);
            $game->theah->eventCheck($resolveEvent);
            $game->theah->queueEvent($resolveEvent);

            $threatEvent = EventFactory::createDuelCalculateTechniqueValuesEvent($actor->Id, $adversaryId, $copy->Id);
            $game->theah->eventCheck($threatEvent);
            $game->theah->queueEvent($threatEvent);

            $game->gamestate->nextState();
        }
    }

}
