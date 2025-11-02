<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01075;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;

class _01075 extends FactionAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Tabard of the Fallen Musketeer");
        $this->Image = "img/cards/7s5s/075.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Montaigne';
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 1;

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 0;

        $this->Traits = [
            'Attire',
            'Tabbard',
            'Oathsworn',
            'Unique',            
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01075(),
        ];
    }

    public function eventCheck(Event $event)
    {
        if ($event instanceof EventAttachmentEquipped && $event->attachmentId === $this->Id)
        {
            $performer = $event->theah->getCharacterById($event->characterId);
            if ($performer->hasTrait("Diplomat"))
            {
                throw new \BgaUserException($event->theah->game->translate("You cannot Equip Tabard of the Fallen Musketeer to a Diplomat."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return ! $character->hasTrait("Diplomat");
    }
}
