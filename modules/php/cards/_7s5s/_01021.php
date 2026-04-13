<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;

class _01021 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Legion's Caress");
        $this->Image = "01021.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 21;
        
        $this->initializeFaction("Vodacce");

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->CanEquipToOpponents = true;

        $this->Traits = [
            clienttranslate("Poison"),
            clienttranslate("Sabotage"),
            clienttranslate("Unique")
        ];

        $this->Text = clienttranslate("<p>May equip to any non-Leader character at a City location.</p><p><b>Forced:</b> After the equipped character en gardes • Wound them.</p>");

        $this->resetCard();
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->hasTrait("Leader"))
            {
                throw new \BgaUserException($event->theah->game->translate("Legion's Caress can only be equipped to a non-Leader character."));
            }

            if (!$event->theah->cardInCity($character))
            {
                throw new \BgaUserException($event->theah->game->translate("Legion's Caress can only be equipped to a character in the city."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardEngarded && $event->cardId == $this->AttachedToId)
        {
            $game = $event->theah->game;
            $character = $event->theah->getCharacterById($event->cardId);
            $game->notify->all("message", clienttranslate('${attachment_inject_code}: Effect triggers and wounds ${character_inject_code}.'), [
                "attachment_inject_code" => $this->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $event->theah->queueEvent(EventFactory::createCharacterBeingWoundedEvent($character->Id, $this->Id, 1, $this->getInjectCode()));
        }
    }
}