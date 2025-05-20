<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class _01073 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Cavalier Hat";
        $this->Image = "img/cards/7s5s/073.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Montaigne';
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 2;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 2;

        $this->Traits = [
            'Attire',
            'Hat',
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id) 
        {
            $performer = $event->theah->getCardById($event->performerId);
            if (!in_array("Duelist", $performer->Traits))
                throw new \BgaUserException($event->theah->game->translate(("Cavalier Hat can only be equipped to Duelists.")));
        }
    }

}
