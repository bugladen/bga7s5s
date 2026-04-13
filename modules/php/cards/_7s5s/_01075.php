<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01075;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _01075 extends FactionAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Tabard of the Fallen Musketeer");
        $this->Image = "01075.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction('Montaigne');
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 1;

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Attire'),
            clienttranslate('Tabbard'),
            clienttranslate('Oathsworn'),
            clienttranslate('Unique'),            
        ];

        $this->Text = clienttranslate("<p>May only equip to your non-Diplomat and they gain Musketeer.</p><p><b>City Action:</b> Engage the equipped performer • Pressure with [Influence]. You succeed even if tied. If successful, claim this location.</p>");

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

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $game = $event->theah->game;
            $character = $event->theah->getCharacterById($event->characterId);
            $character->addTrait($game, "Musketeer");
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $game = $event->theah->game;
            $character = $event->theah->getCharacterById($event->characterId);
            $character->removeTrait($game, "Musketeer");
        }
    }
}
