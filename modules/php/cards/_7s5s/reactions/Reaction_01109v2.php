<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01140;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01109v2 extends RiskReaction implements ICancelReaction
{
    private int $RiskId;
    // WHY: When canceling a Risk Action, ActionTriggered is deleted so the
    // action never queues its own ActionResolved. Store the announcer so we
    // can fire it — Soline (01089) and other "after an Action resolves"
    // listeners still need a window. 0 = not canceling an Action (Reaction path).
    private int $CancelledActionPlayerId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Cancel a Non-Sorcery Risk Action or Reaction");
        $this->RiskId = 0;
        $this->CancelledActionPlayerId = 0;
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

        if ($event instanceof EventActionActivated && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $risk = $event->theah->getCardById($event->sourceId);
                // WHY: Sorcery Risks + printed "These effects cannot be cancelled." (Celerity, Unsanctioned Duel).
                if ($risk instanceof Risk
                    && $event->playerId != $owner->ControllerId
                    && ! $risk->hasTrait("Sorcery")
                    && ! $risk->effectsCannotBeCancelled())
                {
                    // WHY: De-dupe on this clone's class id. The queue stores
                    // {cardId}_Reaction_01109v2, and the original card's LIKE
                    // '%Reaction_01109%' also matches that string. Two v2 copies
                    // still collapse to one offer; do not point this check back
                    // at Reaction_01109 or a mixed hand de-dupes the wrong card.
                    if (! $event->theah->areTransitionEventsOfTypeForPlayerQueued($owner->ControllerId, "Reaction_01109v2"))
                    {
                        $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->stackEvent($transitionEvent);

                        $this->RiskId = $risk->Id;
                        $this->CancelledActionPlayerId = $event->playerId;
                        $owner->IsUpdated = true;
                    }
                }
            }
        }

        // WHY: Risk Reactions never fire EventActionActivated — they queue
        // EventRiskPlayed + EventRiskReactionTriggered after payment. Guard on
        // a pending RiskReactionTriggered so Action plays (already handled
        // above) do not offer cancel a second time on EventRiskPlayed.
        if ($event instanceof EventRiskPlayed && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $risk = $event->theah->getCardById($event->riskId);
                if ($risk instanceof Risk
                    && $event->playerId != $owner->ControllerId
                    && ! $risk->hasTrait("Sorcery")
                    && ! $risk->effectsCannotBeCancelled()
                    && $event->theah->areRiskReactionTriggeredEventsQueuedForSource($event->riskId)
                    // Same class-id de-dupe as the Action path above.
                    && ! $event->theah->areTransitionEventsOfTypeForPlayerQueued($owner->ControllerId, "Reaction_01109v2"))
                {
                    $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->stackEvent($transitionEvent);

                    $this->RiskId = $risk->Id;
                    $this->CancelledActionPlayerId = 0;
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

            $risk = $game->theah->getCardById($this->RiskId);

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

            //Edge case: Unyielding Loyalty — restore the effect it had cancelled
            if ($risk instanceof _01032)
            {
                foreach ($risk->getReactions() as $reaction)
                {
                    if ($reaction instanceof Reaction_01032)
                    {
                        $reaction->revertCancellation($game->theah);
                    }
                }
            }

            //Delete any cancel Risk ActionTriggered or RiskReactionTriggered events.
            //Also delete the queued EventRiskPlayed for Action-path cancels —
            //EventActionActivated fires before EventRiskPlayed, so the played
            //event is still queued and would otherwise let other "after a Risk
            //is played" reactions fire on the cancelled card. (Reaction-path
            //cancels see EventRiskPlayed as the current event, already dequeued.)
            $game->theah->deleteActionTriggeredEvents($this->RiskId);
            $game->theah->deleteRiskReactionTriggeredEvents($this->RiskId);
            $game->theah->deleteRiskPlayedEvents($this->RiskId);

            // WHY: Deleting ActionTriggered means the cancelled Risk Action never
            // reaches createActionResolvedEvent in its effect path. Without this,
            // HIGH_DRAMA_PLAYER_TURN_EVENTS hits endOfEvents → NEXT_PLAYER and
            // Soline (Reaction_01089) never gets her "after an Action resolves"
            // window. Costs were paid; the Action sequence is complete with
            // effects cancelled. Only fire for Action cancels (not Reaction).
            if ($this->CancelledActionPlayerId > 0)
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($this->CancelledActionPlayerId);
                $game->theah->queueEvent($actionResolvedEvent);
                $this->CancelledActionPlayerId = 0;
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