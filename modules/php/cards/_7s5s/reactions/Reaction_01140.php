<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01140 extends RiskReaction
{
    private ?EventCardMoved $eventCardMoved = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Your Character Movement");
        $this->eventCardMoved = null;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to cancel your character movement: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Movement'), 'cancel');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                // Skip if this is the event we already processed and stored
                if ($this->eventCardMoved !== null && 
                    $this->eventCardMoved->cardId == $event->cardId &&
                    $this->eventCardMoved->fromLocation == $event->fromLocation &&
                    $this->eventCardMoved->toLocation == $event->toLocation &&
                    $this->eventCardMoved->initiatingPlayerId == $event->initiatingPlayerId)
                {
                    return; // This is the event we're about to release, don't catch it again
                }
                
                $owner = $this->getOwningCard($event->theah);
                $card = $event->theah->getCardById($event->cardId);
                if ($card instanceof Character && $card->ControllerId == $owner->ControllerId)
                {
                    $owner->IsUpdated = true;
                    $this->eventCardMoved = clone $event;
                    unset($this->eventCardMoved->theah);

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
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
            $game->theah->queueEvent($event);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $this->setUsed($game->theah, true);
        }

        if ($reactionId == 'pass')
        {
            $game->theah->queueEvent($this->eventCardMoved);
        }

        $game->gamestate->nextState("done");
    }

}