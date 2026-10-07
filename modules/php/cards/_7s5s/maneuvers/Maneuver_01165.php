<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
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

        return count($this->getCopyableTechniques($theah, $adversary)) > 0;
    }

    /**
     * WHY not isAvailableToPlayer: Trick copies effects only — source costs /
     * prerequisites (e.g. Sabre Technique_04054b requiring the attachment's
     * character to be the duel actor) must not gate what can be copied.
     * Same shape as Dame (02055) / Yepikhodov (03051).
     *
     * @return Technique[]
     */
    private function getCopyableTechniques(Theah $theah, Character $adversary): array
    {
        $techniquesArray = [];

        if ($adversary instanceof IHasTechniques)
        {
            foreach ($adversary->getTechniques() as $technique)
            {
                if ($this->isCopyableTechnique($technique))
                {
                    $techniquesArray[] = $technique;
                }
            }
        }

        foreach ($adversary->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if (! ($attachment instanceof IHasTechniques))
            {
                continue;
            }

            foreach ($attachment->getTechniques() as $technique)
            {
                if ($this->isCopyableTechnique($technique))
                {
                    $techniquesArray[] = $technique;
                }
            }
        }

        return $techniquesArray;
    }

    private function isCopyableTechnique(Technique $technique): bool
    {
        if ($technique->IsTemporaryCopy)
        {
            return false;
        }

        return true;
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
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->removeCopiedTechniques($event->theah);
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01165)
        {
            $adversary = $game->theah->getDuelRoundOpponent();
            $techniquesArray = $this->getCopyableTechniques($game->theah, $adversary);
            // WHY both casings: getPropertyArray is lowercase (Dame shape). Older JS for
            // this state read technique.Id / technique.Name from raw objects — dual keys
            // keep buttons labeled if only PHP is redeployed / JS is cached.
            $args['techniques'] = array_values(array_map(function (Technique $t) use ($game) {
                $props = $t->getPropertyArray($game);
                $props['Id'] = $props['id'];
                $props['Name'] = $props['name'];
                return $props;
            }, $techniquesArray));
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
            if ($technique === null)
            {
                throw new UserException($game->translate("Invalid technique ID: {$id}"));
            }

            $copy = clone $technique;
            $copy->setOwnerId($actor->Id);
            // WHY: Distinct from source Id (adversaryId_ClassId) and from a second
            // copy of the same ClassId. Dame / Yepikhodov use the same shape.
            $copy->Id = $actor->Id . "_copy_" . $copy->ClassId;
            // WHY: Engage-as-cost techniques (Sabre 04054b, etc.) skip re-engage when
            // this is set; base Technique also self-removes on DuelNewRound / DuelEnd.
            $copy->IsTemporaryCopy = true;
            $copy->Used = false;

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

            $game->gamestate->nextState("cardChosen");
        }
    }

}
