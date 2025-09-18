<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01025_Burden extends Attachment
{
    public int $OriginalCardId = 0;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Fate's Burden");
        $this->Image = "img/cards/7s5s/025.jpg";
        $this->ShowStatModifiers = false;
        $this->OriginalCardId = 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardEngarded && $this->isAttached())
        {
            if ($event->cardId == $this->AttachedToId)
            {
                $event->canceled = true;

                $game = $event->theah->game;
                $attachedTo = $event->theah->getCardById($this->AttachedToId);
                $game->notifyAllPlayers("message", clienttranslate('${burden_inject_code} prevents ${card_inject_code} from En Garding'), [
                    "burden_inject_code" => $this->getInjectCode(),
                    "card_inject_code" => $attachedTo->getInjectCode(),
                ]);

                $this->removeBurden($event->theah);
            }
        }

        if ($event instanceof EventDuskEndOfDay && $this->isAttached())
        {
            $this->removeBurden($event->theah);
        }
    }

    private function removeBurden(Theah $theah)
    {
        //Place this card in discard pile, then remove it and hide it silently
        $unequipEvent = EventFactory::createAttachmentUnequippedEvent($this->OwnerId, $this->AttachedToId, $this->Id);
        $theah->queueEvent($unequipEvent);
        $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($this->OwnerId, $this->Id, $this->Location);
        $theah->queueEvent($discardEvent);
        $hiddenEvent = EventFactory::createCardHiddenEvent($this->OwnerId, $this->Id);
        $theah->queueEvent($hiddenEvent);

        //Place the original Risk card in the discard pile
        $originalCard = $theah->getCardById($this->OriginalCardId);

        $discardPileName = $theah->game->getPlayerDiscardDeckName($this->OwnerId);
        $deck = $theah->game->getGameDeckObject();
        $deck->moveCard($this->OriginalCardId, $discardPileName);
        
        $originalCard->Location = $discardPileName;
        $theah->game->updateCardObjectInDb($originalCard);
    }
}