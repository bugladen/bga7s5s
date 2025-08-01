<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

class Reaction_01146b extends CardReaction
{
    public string $TechniqueId;
    public string $ManeuverId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel adversary's Maneuver or Technique");
        $this->TechniqueId = '';
        $this->ManeuverId = '';
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to cancel an adversary\'s Maneuver or Technique: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $text = $this->ManeuverId != '' ? $theah->game->translate('Cancel Maneuver') : $theah->game->translate('Cancel Technique');
        $array[] = $this->createButtonProperty($theah->game, $text, 'cancel');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventTechniqueActivated)
        {
            $scheme = $this->getOwningCard($event->theah);
            $owner = $event->theah->getCardById($event->ownerId);
            if ($owner->ControllerId != $scheme->ControllerId)
            {
                $reactionEvent = EventFactory::createReactionTransitionEvent($scheme->ControllerId, $scheme->Id, $this->Id);
                $reactionEvent->priority = Event::HIGH_PRIORITY;
                $event->theah->queueEvent($reactionEvent);

                $this->TechniqueId = $event->techniqueId;
                $scheme->IsUpdated = true;
            }
        }

        if ($event instanceof EventManeuverActivated)
        {
            $scheme = $this->getOwningCard($event->theah);
            $owner = $event->theah->getCardById($event->ownerId);
            if ($owner->ControllerId != $scheme->ControllerId)
            {
                $reactionEvent = EventFactory::createReactionTransitionEvent($scheme->ControllerId, $scheme->Id, $this->Id);
                $reactionEvent->priority = Event::HIGH_PRIORITY;
                $event->theah->queueEvent($reactionEvent);

                $this->ManeuverId = $event->maneuverId;
                $scheme->IsUpdated = true;
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancel')
        {
            $scheme = $this->getOwningCard($game->theah);

            if ($this->TechniqueId != '')
            {
                $technique = $game->theah->getTechniqueById($this->TechniqueId);
                $game->notifyAllPlayers('message', clienttranslate('<strong>Let The Sword Decide:</strong> ${player_name} chose to cancel Technique: [${technique}]'), [
                    'player_name' => $game->getActivePlayerName(),
                    'technique' => $technique->Name
                ]);
                $game->globals->delete(Game::CHOSEN_TECHNIQUE);
                $game->globals->delete(Game::CHOSEN_TECHNIQUE_IS_MAIN);
                $game->theah->deleteTechniqueEvents($this->TechniqueId);
                $scheme->IsUpdated = true;

                $canceledEvent = EventFactory::createTechniqueCanceledEvent($scheme->ControllerId, $this->TechniqueId);
                $game->theah->queueEvent($canceledEvent);

                $this->TechniqueId = '';
            }

            if ($this->ManeuverId != '')
            {
                $maneuver = $game->theah->getManeuverById($this->ManeuverId);
                $game->notifyAllPlayers('message', clienttranslate('<strong>Let The Sword Decide:</strong> ${player_name} chose to cancel Maneuver: [${maneuver}]'), [
                    'player_name' => $game->getActivePlayerName(),
                    'maneuver' => $maneuver->Name
                ]);
                $game->globals->delete(Game::CHOSEN_MANEUVER);
                $game->theah->deleteManeuverEvents($this->ManeuverId);
                $scheme->IsUpdated = true;

                $canceledEvent = EventFactory::createManeuverCanceledEvent($scheme->ControllerId, $this->ManeuverId);
                $game->theah->queueEvent($canceledEvent);

                $this->ManeuverId = '';
            }

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}