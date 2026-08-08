<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

trait RiskAttachmentTrait
{
    public int $OriginalCardId = 0;

    public function setOriginalCardId(int $originalCardId)
    {
        $this->OriginalCardId = $originalCardId;
    }

    public function removeRiskAttachment(Theah $theah)
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
        $theah->game->moveCard($this->OriginalCardId, $discardPileName, 0, $originalCard);
    }

}