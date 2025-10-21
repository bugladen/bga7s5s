<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01027 extends CancelReaction
{
    private ?EventLocationPressured $event = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Pressure Fails with Difference of 1 or Less');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to for the Pressure to Fail: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Fail Pressure'), 'failPressure');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventLocationPressured && $event->success && $event->difference <= 1)
        {
            $risk = $this->getOwningCard($event->theah);
            if ($risk->Location == Game::LOCATION_HAND)
            {
                $this->event = clone $event;
                unset($this->event->theah);
                
                $owner = $this->getOwningCard($event->theah);
                $owner->IsUpdated = true;
    
                $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $transitionEvent->priority = Event::HIGH_PRIORITY;
                $event->theah->queueEvent($transitionEvent);
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            if ($event->reactionId == "failPressure")
            {
                $game = $event->theah->game;
                $owner = $this->getOwningCard($game->theah);
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: Difference of Pressure was 1 or less. ${player_name} used Reaction and FAILED Pressure.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);
    
                // Delete the existing pressure result event and replace with a failed pressure result event
                $game->theah->deletePressureResultEvents();
                $event = EventFactory::createLocationPressureResultEvent(
                    $this->event->playerId, 
                    $this->event->performerId, 
                    $this->event->location, 
                    $this->event->pressureType, 
                    false, 
                    $this->event->totalsExplanation, 
                    $this->event->highDramaBasicAction, 
                    $this->event->abilityId);
                $game->theah->queueEvent($event);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "failPressure")
        {
            $game->gamestate->nextState('pay');
            return;
        }

        $game->gamestate->nextState("done");
    }
}