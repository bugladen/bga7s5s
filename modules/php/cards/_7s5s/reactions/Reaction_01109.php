<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01109 extends CancelReaction
{
    private int $RiskId;
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Cancel a Sorcery Risk Card");
        $this->RiskId = 0;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to cancel the Sorcery Risk just played: ');
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
                    $transitionEvent->priority = Event::HIGHEST_PRIORITY;
                    $event->theah->stackEvent($transitionEvent);
    
                    $this->RiskId = $event->riskId;
                    $owner->IsUpdated = true;
                }
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

            //Delete any cancel Risk ActionTriggered or RiskReactionTriggered events
            $game->theah->deleteActionTriggeredEvents($this->RiskId);
            $game->theah->deleteRiskReactionTriggeredEvents($this->RiskId);
            $this->RiskId = 0;
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancel')
        {
            $game->gamestate->nextState("pay");
            return;
        }

        $game->gamestate->nextState("done");
    }
}