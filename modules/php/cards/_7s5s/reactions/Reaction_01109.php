<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01140;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01169;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01109 extends RiskReaction implements ICancelReaction
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
        $base = parent::getReactionDescription($theah);
        $risk = $theah->getCardById($this->RiskId);
        if ($risk !== null)
        {
            return $base . sprintf($theah->game->translate('${you} may choose to cancel %s: '), $theah->game->translate($risk->Name));
        }
        return $base . $theah->game->translate('${you} may choose to cancel the Risk just played: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $risk = $theah->getCardById($this->RiskId);
        if ($risk !== null)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Cancel %s'), $theah->game->translate($risk->Name)), 'cancel');
        }
        else
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Risk'), 'cancel');
        }
        
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventRiskPlayed && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
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
        }
        
        if ($event instanceof EventManeuverActivated && $this->isAvailable())
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
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
        }

        //Edge case: Not Today
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->isAvailable())
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $card = $event->theah->getCardById($event->combatCardId);
                if ($card->ControllerId != $owner->ControllerId && $card instanceof _01169)
                {
    
                    $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->stackEvent($reactionEvent);                
    
                    $this->RiskId = $event->combatCardId;
                    $owner->IsUpdated = true;
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            $game->notify->all("message", clienttranslate('${player_name} uses ${reaction_inject_code} to cancel the Risk just played.'), [
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'reaction_inject_code' => $owner->getInjectCode(),
            ]);

            //Edge case: Not Today
            $risk = $game->theah->getCardById($this->RiskId);
            if ($risk instanceof _01169)
            {
                $risk->cancelEscape();
            }

            //Edge case: Stubborn — restore the movement that Stubborn had cancelled
            if ($risk instanceof _01140)
            {
                foreach ($risk->getReactions() as $reaction)
                {
                    if ($reaction instanceof Reaction_01140)
                    {
                        $reaction->revertCancellation($game->theah);
                    }
                }
            }

            //Delete any cancel Risk ActionTriggered or RiskReactionTriggered events
            $game->theah->deleteActionTriggeredEvents($this->RiskId);
            $game->theah->deleteRiskReactionTriggeredEvents($this->RiskId);

            // Cancel Maneuver if it exists
            if ($this->ManeuverId !== '')
            {
                $game->theah->deleteManeuverEvents($this->ManeuverId);
                $this->ManeuverId = '';
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

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $event->priority = Event::HIGHEST_PRIORITY;
            $game->theah->stackEvent($event);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}