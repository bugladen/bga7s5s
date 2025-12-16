<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class _01073 extends FactionAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cavalier Hat");
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
        $this->DashedParry = true;
        $this->Thrust = 2;

        $this->Traits = [
            'Attire',
            'Hat',
        ];

        $this->resetCard();
        
        $this->Actions = [
            new Action_01073(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id) 
        {
            $performer = $event->theah->getCardById($event->characterId);
            if (! $performer->hasTrait("Duelist"))
            {
                throw new \BgaUserException($event->theah->game->translate(("Cavalier Hat can only be equipped to Duelists.")));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $character->hasTrait("Duelist");
    }
}
