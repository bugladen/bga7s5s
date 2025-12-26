<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;

class _01154_RiskClone extends Risk implements IHasActions
{
    use ActionTrait;

    public int $AttachmentId = 0;
    public int $ClonedCardId = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardDiscardedFromHand && $event->cardId == $this->Id)
        {
            //Remove the clone from the discard pile and hide it
            $game = $event->theah->game;
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($event->ownerId, $this->Id);
            $event->theah->queueEvent($removeEvent);
            $deck = $game->getGameDeckObject();
            $deck->moveCard($this->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            //Discard the cloned card to the player's discard pile
            $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
            $deck->moveCard($clonedCard->Id, $game->getPlayerDiscardDeckName($clonedCard->ControllerId));
            $game->notify->all("cardAddedToPlayerDiscardPile", "", [
                "playerId" => $clonedCard->ControllerId,
                "card" => $clonedCard->getPropertyArray($game),                
            ]);

            //Move Corpse Speak to the Locker
            $attachment = $game->theah->getAttachmentById($this->AttachmentId);

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($attachment->ControllerId, $attachment->AttachedToId, $attachment->Id);
            $event->theah->queueEvent($unequipEvent);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($attachment->ControllerId, $attachment->Id);
            $event->theah->queueEvent($lockerEvent);
        }
    }
}
