<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskAttachmentTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class _01025_Burden extends Attachment implements IRiskAttachment
{
    use RiskAttachmentTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Fate's Burden");
        $this->Image = "01025v2.jpg";

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Sorte'),
        ];

        $this->ShowStatModifiers = false;
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
                $game->notify->all("message", clienttranslate('${burden_inject_code} prevents ${card_inject_code} from En Garding'), [
                    "burden_inject_code" => $this->getInjectCode(),
                    "card_inject_code" => $attachedTo->getInjectCode(),
                ]);

                $this->removeRiskAttachment($event->theah);
            }
        }

        if ($event instanceof EventHighDramaPhaseEnd && $this->isAttached())
        {
            $this->removeRiskAttachment($event->theah);
        }
    }
}