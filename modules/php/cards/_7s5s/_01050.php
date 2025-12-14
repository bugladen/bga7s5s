<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _01050 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Unsavory Salve");
        $this->Image = "img/cards/7s5s/050.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 50;

        $this->initializeFaction('Eisen');

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->resetCard();

        $this->Techniques = [
            new Technique_01050(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if (! $character->hasWeaponEquipped($event->theah))
            {
                throw new \BgaUserException($event->theah->game->translate("Unsavory Salve can only be equipped to a character with a weapon equipped."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentUnequipped && $this->isAttached())
        {
            $owner = $this->attachedTo($event->theah);
            if ($owner instanceof Character && ! $owner->hasWeaponEquipped($event->theah))
            {
                $event->theah->game->notify->all("message", clienttranslate('${attachment_inject_code} will be discarded from ${character_inject_code} because they no longer have a weapon equipped.'), [
                    "attachment_inject_code" => $this->getInjectCode(),
                    "character_inject_code" => $owner->getInjectCode(),
                ]);

                $unequipEvent = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($unequipEvent);

                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($this->ControllerId, $this->Id, $this->Location, $this->Id, $asEffect = true);
                $event->theah->queueEvent($discardEvent);
            }
        }
    }
}
