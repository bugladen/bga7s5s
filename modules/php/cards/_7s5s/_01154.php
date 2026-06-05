<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01154;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class _01154 extends FactionAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Corpse Speak");
        $this->Image = "01154.jpg";
        $this->ExpansionName = "_7s5s";
        $this->CardNumber = 154;
        $this->ExpansionNumber = 1;
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->InfluenceLocked = true;
        $this->InfluenceLockedValue = 0;

        $this->Traits = [
            clienttranslate("Sorcery"),
            clienttranslate("Unguent")
        ];

        $this->Text = clienttranslate("<p>May only equip to your Sorcerer. The equipped character's [Influence] is set to 0.</p><p><b>Action:</b> Engage the equipped performer • Play target risk from your discard pile as if it was from your hand. Send this card to The Locker.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01154(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if (! $character->hasTrait("Sorcerer"))
            {
                throw new \BgaUserException($event->theah->game->translate("Corpse Speak can only be equipped to a Sorcerer."));
            }
        }
    }
}