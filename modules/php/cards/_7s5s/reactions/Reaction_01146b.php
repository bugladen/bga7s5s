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

        $this->Name = clienttranslate("Cancel Adversary's Maneuver or Technique");
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

        if ($event instanceof EventTechniqueActivated && $this->isAvailable())
        {
            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL);
            //Only can react if in duel, per rules team
            //https://discord.com/channels/643518732999589892/1195443554944815114
            if ($inDuel)
            {
                $scheme = $this->getOwningCard($event->theah);
                $owner = $event->theah->getCardById($event->ownerId);
                if ($owner->ControllerId != $scheme->ControllerId)
                {
                    $reactionEvent = EventFactory::createReactionTransitionEvent($scheme->ControllerId, $scheme->Id, $this->Id);
                    $reactionEvent->priority = Event::HIGH_PRIORITY;
                    $event->theah->queueEvent($reactionEvent);
    
                    $this->TechniqueId = $event->techniqueId;
                    $this->ManeuverId = '';
                    $scheme->IsUpdated = true;
                }
            }
        }

        if ($event instanceof EventManeuverActivated && $this->isAvailable())
        {
            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL);
            //Only can react if in duel, per rules team
            //https://discord.com/channels/643518732999589892/1195443554944815114
            if ($inDuel)
            {
                $scheme = $this->getOwningCard($event->theah);
                $owner = $event->theah->getCardById($event->ownerId);
                if ($owner->ControllerId != $scheme->ControllerId)
                {
                    $reactionEvent = EventFactory::createReactionTransitionEvent($scheme->ControllerId, $scheme->Id, $this->Id);
                    $reactionEvent->priority = Event::HIGH_PRIORITY;
                    $event->theah->queueEvent($reactionEvent);
    
                    $this->ManeuverId = $event->maneuverId;
                    $this->TechniqueId = '';
                    $scheme->IsUpdated = true;
                }
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
                $game->notifyAllPlayers('message', clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and chose to cancel Technique: [${technique}]'), [
                    "i18n" => ["technique"],
                    'reaction_inject_code' => $scheme->getInjectCode(),
                    'player_name' => $game->getActivePlayerName(),
                    'technique' => $technique->Name,
                ]);
                $game->globals->delete(Game::CHOSEN_TECHNIQUE);
                $game->globals->delete(Game::CHOSEN_TECHNIQUE_IS_MAIN);
                $game->theah->deleteTechniqueEvents($this->TechniqueId);
                $scheme->IsUpdated = true;

                $canceledEvent = EventFactory::createTechniqueCanceledEvent($scheme->ControllerId, $this->TechniqueId);
                $game->theah->queueEvent($canceledEvent);

                $this->TechniqueId = '';
                $scheme->IsUpdated = true;
            }

            if ($this->ManeuverId != '')
            {
                $maneuver = $game->theah->getManeuverById($this->ManeuverId);
                $game->notifyAllPlayers('message', clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and chose to cancel Maneuver: [${maneuver}]'), [
                    "i18n" => ["maneuver"],
                    'reaction_inject_code' => $scheme->getInjectCode(),
                    'player_name' => $game->getActivePlayerName(),
                    'maneuver' => $maneuver->Name,
                ]);
                $game->globals->delete(Game::CHOSEN_MANEUVER);
                $game->theah->deleteManeuverEvents($this->ManeuverId);
                $scheme->IsUpdated = true;

                $canceledEvent = EventFactory::createManeuverCanceledEvent($scheme->ControllerId, $this->ManeuverId);
                $game->theah->queueEvent($canceledEvent);

                $this->ManeuverId = '';
                $scheme->IsUpdated = true;
            }

            $this->setUsed($game->theah, true);
        }

        if ($reactionId == 'decline')
        {
            $this->TechniqueId = '';
            $this->ManeuverId = '';
            $scheme = $this->getOwningCard($game->theah);
            $scheme->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}