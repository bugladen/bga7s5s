<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01027 extends RiskReaction implements ICancelReaction
{
    private ?EventLocationPressured $event = null;

    // WHY: actPayForReaction reads Game::REACTION_ID. A leftover sibling Objection prompt
    // (starter decks run two) can Pass and overwrite that global to "pass" before this
    // copy's pay fires EventRiskReactionTriggered — which would then skip fail cleanup.
    // Same discipline as Hexenjagd (Reaction_01053).
    private string $chosenCancelReactionId = '';

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
            if ($event->playerId != $risk->ControllerId && $risk->Location == Game::LOCATION_HAND)
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
            $reactionId = $event->reactionId;
            if ($this->chosenCancelReactionId !== ''
                && ($reactionId === 'pass' || $reactionId === 'decline'))
            {
                $reactionId = $this->chosenCancelReactionId;
            }

            if ($reactionId == "failPressure")
            {
                $game = $event->theah->game;
                $owner = $this->getOwningCard($game->theah);
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: Difference of Pressure was 1 or less. ${player_name} used Reaction and FAILED Pressure.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);
    
                // Delete the existing pressure result event and replace with a failed pressure result event
                $game->theah->deletePressureResultEvents();
                $resultEvent = EventFactory::createLocationPressureResultEvent(
                    $this->event->playerId, 
                    $this->event->performerId, 
                    $this->event->location, 
                    $this->event->pressureType, 
                    false, 
                    $this->event->totalsExplanation, 
                    $this->event->highDramaBasicAction, 
                    $this->event->abilityId);
                $game->theah->queueEvent($resultEvent);

                $this->chosenCancelReactionId = '';
                // Belt-and-suspenders: performReaction already cleared siblings when fail
                // was chosen; clear again in case a late offer stacked during pay.
                $this->clearSiblingObjectionReactionTransitions($game, $owner->Id, $owner->ControllerId);
                $owner->IsUpdated = true;
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "failPressure")
        {
            $owner = $this->getOwningCard($game->theah);
            $this->chosenCancelReactionId = $reactionId;
            $owner->IsUpdated = true;

            // WHY: Starter decks run two Objection. LocationPressured stacks a reaction
            // transition per copy; without clearing siblings here, the second copy prompts
            // while this copy is paying — Pass overwrites REACTION_ID so Triggered skips
            // fail cleanup (Hexenjagd Reaction_01053 / Mireli Reaction_01135).
            $this->clearSiblingObjectionReactionTransitions($game, $owner->Id, $owner->ControllerId);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->stackEvent($event);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->stackEvent($event);
       }

        $game->gamestate->nextState("done");
    }

    private function clearSiblingObjectionReactionTransitions(Game $game, int $ownerCardId, int $controllerId): void
    {
        $handCards = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $controllerId);
        foreach ($handCards as $handCard)
        {
            if ($handCard->Id == $ownerCardId || ! ($handCard instanceof IHasReactions))
            {
                continue;
            }

            foreach ($handCard->getReactions() as $otherReaction)
            {
                if ($otherReaction instanceof Reaction_01027)
                {
                    $game->theah->deleteTransitionEvents($otherReaction->Id);
                    $game->theah->deleteTransitionEventsBySourceId($handCard->Id);
                }
            }
        }
    }
}
