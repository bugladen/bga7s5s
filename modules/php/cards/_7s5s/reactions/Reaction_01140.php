<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01140 extends CancelReaction
{
    private ?EventCardMoving $eventCardMoving = null;
    private string $cardName = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Your Character Movement");
        $this->eventCardMoving = null;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('${you} may choose to cancel the movement of %s: '), $theah->game->translate($this->cardName));
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Movement'), 'cancel');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoving && ! $event->canceled && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                // Skip if this card already declined to cancel this event
                if (in_array($owner->Id, $event->cancelDeclinedByCardIds))
                {
                    return;
                }

                $owner = $this->getOwningCard($event->theah);
                $card = $event->theah->getCardById($event->cardId);
                if ($card instanceof Character && $card->ControllerId == $owner->ControllerId)
                {
                    $owner->IsUpdated = true;
                    $this->eventCardMoving = clone $event;
                    unset($this->eventCardMoving->theah);
                    $this->cardName = $card->Name;
                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->stackEvent($reactionTransitionEvent);
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and cancelled their character movement.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancel')
        {
            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->stackEvent($event);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->stackEvent($event);

            $this->setUsed($game->theah, true);
        }

        if ($reactionId == 'decline')
        {
            $owner = $this->getOwningCard($game->theah);
            
            // Mark the event as declined by this card so other copies won't catch it
            $this->eventCardMoving->cancelDeclinedByCardIds[] = $owner->Id;
            
            $game->theah->queueEvent($this->eventCardMoving);

            $this->eventCardMoving = null;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }

}