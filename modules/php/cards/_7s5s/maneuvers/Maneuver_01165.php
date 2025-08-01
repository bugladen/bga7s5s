<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01165 extends Maneuver
{
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

        $actor = $theah->getDuelRoundActor($playerId);
        $adversaryId = $theah->getDuelOpponentId($actor);
        $adversary = $theah->getCharacterById($adversaryId);

        $techniques = $adversary instanceof IHasTechniques ? $adversary->getTechniques() : [];
        if (count($techniques) > 0)
            return true;

        foreach ($adversary->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            $techniques = $attachment instanceof IHasTechniques ? $attachment->getTechniques() : [];
            if (count($techniques) > 0)
                return true;
        }

        return false;
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
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01165)
        {
            $playerId = $game->getActivePlayerId();
            $actor = $game->theah->getDuelRoundActor($playerId);
            $adversaryId = $game->theah->getDuelOpponentId($actor);
            $adversary = $game->theah->getCharacterById($adversaryId);

            $techniquesArray = [];
            $techniques = $adversary instanceof IHasTechniques ? $adversary->getTechniquesAvailableToPlayer($game, $playerId) : [];
            if (count($techniques) > 0)
                $techniquesArray += $techniques;
    
            foreach ($adversary->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                $techniques = $attachment instanceof IHasTechniques ? $attachment->getTechniquesAvailableToPlayer($game, $playerId) : [];
                if (count($techniques) > 0)
                    $techniquesArray += $techniques;
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
            $technique = $game->theah->getTechniqueById($id);
            $game->globals->set(Game::CHOSEN_TECHNIQUE, $technique->Id);
            $game->globals->set(Game::CHOSEN_TECHNIQUE_IS_MAIN, false);
            $game->globals->set(GAME::TRANSITION_INTERNAL_ID, $technique->Id);

            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor);
            $owner = $this->getOwningCard($game->theah);

            $activateEvent = EventFactory::createTechniqueActivatedEvent($actor->ControllerId, $owner->Id, $technique->Id, $copied = true);
            $game->theah->eventCheck($activateEvent);
            $game->theah->queueEvent($activateEvent);

            $resolveEvent = EventFactory::createResolveTechniqueEvent($actor->ControllerId, $actor->Id, $adversaryId, $technique->Id);
            $game->theah->eventCheck($resolveEvent);
            $game->theah->queueEvent($resolveEvent);

            $threatEvent = EventFactory::createDuelCalculateTechniqueValuesEvent($actor->Id, $adversaryId, $technique->Id);
            $game->theah->eventCheck($threatEvent);
            $game->theah->queueEvent($threatEvent);

            //Going to call this manually as we are not going to use the normal flow.
            //This will call the next state, which is the one we want.
            $game->stApplyCombatCardStats();
        }
     }
}
