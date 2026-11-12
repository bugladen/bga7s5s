<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskClonePropertyTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;

class _01154_RiskClone extends Risk implements IHasActions
{
    use ActionTrait;
    use RiskClonePropertyTrait;

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
            // WHY permanentlyHide queued (not moveCard here): see _01106_RiskClone —
            // discard hub runs after cards and would undo an immediate hide.
            $game = $event->theah->game;
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent(
                $event->ownerId,
                $this->Id,
                $messageHidden = false,
                $permanentlyHide = true
            );
            $event->theah->queueEvent($removeEvent);

            //Discard the cloned card to the player's discard pile
            $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
            $discardPileName = $game->getPlayerDiscardDeckName($clonedCard->ControllerId);
            $game->moveCard($clonedCard->Id, $discardPileName, 0, $clonedCard);

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

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($attachment->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
