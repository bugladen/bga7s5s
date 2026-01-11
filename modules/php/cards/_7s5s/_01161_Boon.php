<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskAttachmentTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class _01161_Boon extends Attachment implements IRiskAttachment
{
    use RiskAttachmentTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Boon");
        $this->Image = "01161.jpg";
        $this->CombatModifier = 1;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 1;
        
        $this->Traits = [
            'Sorcery',
            'Glamour',
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay && $this->isAttached())
        {
            $this->removeRiskAttachment($event->theah);
        }
    }

}