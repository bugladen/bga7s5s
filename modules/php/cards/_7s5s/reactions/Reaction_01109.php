<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01169;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01109 extends CancelReaction
{
    private int $RiskId;
    private string $ManeuverId = '';
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Cancel a Non-Sorcery Risk Card");
        $this->RiskId = 0;
        $this->ManeuverId = '';
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to cancel the Risk just played: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Risk'), 'cancel');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventRiskPlayed && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            $risk = $event->theah->getCardById($event->riskId);
            if ($risk instanceof Risk && $risk->ControllerId != $owner->ControllerId && ! $risk->hasTrait("Sorcery"))
            {
                //Make sure there is not another copy of this reaction queued
                if (! $event->theah->areTransitionEventsOfTypeForPlayerQueued($owner->ControllerId, "Reaction_01109"))
                {
                    $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->stackEvent($transitionEvent);
    
                    $this->RiskId = $event->riskId;
                    $owner->IsUpdated = true;
                }
            }
        }
        
        if ($event instanceof EventManeuverActivated && $this->isAvailable())
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $maneuver = $event->theah->getManeuverById($event->maneuverId);
            $risk = $maneuver->getOwningCard($event->theah);
            if ($event->playerId != $owner->ControllerId && ! $risk->hasTrait("Sorcery") && !$maneuver instanceof ISorcererAbility)
            {
                $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->stackEvent($reactionEvent);

                $this->RiskId = $risk->Id;
                $this->ManeuverId = $event->maneuverId;
                $owner->IsUpdated = true;
            }
        }

        //Edge case: Not Today
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->isAvailable())
        {
            $game = $event->theah->game;
            $card = $event->theah->getCardById($event->combatCardId);
            $owner = $this->getOwningCard($event->theah);
            if ($event->actorId != $owner->ControllerId && $card instanceof _01169)
            {

                $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->stackEvent($reactionEvent);                

                $this->RiskId = $event->combatCardId;
                $owner->IsUpdated = true;
            }

        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            $game->notify->all("message", clienttranslate('${player_name} uses ${reaction_inject_code} to cancel the Risk just played.'), [
                'player_name' => $game->getActivePlayerName(),
                'reaction_inject_code' => $owner->getInjectCode(),
            ]);

            //Edge case: Not Today
            $risk = $game->theah->getCardById($this->RiskId);
            if ($risk instanceof _01169)
            {
                $risk->cancelEscape();
            }

            //Delete any cancel Risk ActionTriggered or RiskReactionTriggered events
            $game->theah->deleteActionTriggeredEvents($this->RiskId);
            $game->theah->deleteRiskReactionTriggeredEvents($this->RiskId);

            // Cancel Maneuver if it exists
            if ($this->ManeuverId != 0)
            {
                $game->theah->deleteManeuverEvents($this->ManeuverId);
                $this->ManeuverId = 0;
            }

            $this->RiskId = 0;
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancel')
        {
            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->priority = Event::HIGHEST_PRIORITY;
            $game->theah->stackEvent($event);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION);
            $event->priority = Event::HIGHEST_PRIORITY;
            $game->theah->stackEvent($event);
        }

        $game->gamestate->nextState("done");
    }
}