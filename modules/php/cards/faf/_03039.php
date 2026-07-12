<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03039;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _03039 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Íñigo Rocoso');
        $this->Title = clienttranslate('Avispa Mordedora');
        $this->Image = '03039.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 39;
        $this->InPlayXImageOffset = -20;

        $this->initializeFaction('Castille');

        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Scoundrel'),
            clienttranslate('Duelist'),
            clienttranslate('Castille')
        ];

        $this->Text = clienttranslate("<p>While Íñigo is equipped with a <b>Weapon</b>, he gains +1[Finesse].</p>
<p><b>Gambling Technique:</b> -2[Thrust] • The adversary discards a card. Then, if they have more cards in hand than you, en garde Íñigo. At the end of the round, move Íñigo <b>Home</b>. <i>(Your combat card must have at least 2 [Thrust].)</i></p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_03039(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Mirror Rena (_01040) Weapon Combat bonus — apply +1 Finesse only on the
        // transition into "has at least one Weapon" (weaponsCount == 1 after equip), and
        // undo only when the last Weapon leaves (weaponsCount == 0 after unequip). Counting
        // after the equip/unequip event means Attachments already reflects the new set.
        if ($event instanceof EventAttachmentEquipped && $event->characterId == $this->Id)
        {
            $newAttachment = $event->theah->getAttachmentById($event->attachmentId);
            if ($newAttachment->hasTrait("Weapon"))
            {
                $weaponsCount = 0;
                foreach ($this->Attachments as $attachmentId)
                {
                    $attachment = $event->theah->getAttachmentById($attachmentId);
                    if ($attachment->hasTrait("Weapon"))
                    {
                        $weaponsCount++;
                    }
                }

                if ($weaponsCount == 1)
                {
                    $modifiedEvent = EventFactory::createCharacterFinesseModifedEvent(
                        $this->ControllerId,
                        $this->Id,
                        $this->ModifiedFinesse,
                        $this->ModifiedFinesse + 1,
                        $this->getInjectCode()
                    );
                    $event->theah->queueEvent($modifiedEvent);
                }
            }
        }

        if ($event instanceof EventAttachmentUnequipped && $event->characterId == $this->Id)
        {
            $removedAttachment = $event->theah->getAttachmentById($event->attachmentId);
            if ($removedAttachment->hasTrait("Weapon"))
            {
                $weaponsCount = 0;
                foreach ($this->Attachments as $attachmentId)
                {
                    $attachment = $event->theah->getAttachmentById($attachmentId);
                    if ($attachment->hasTrait("Weapon"))
                    {
                        $weaponsCount++;
                    }
                }

                if ($weaponsCount == 0)
                {
                    $modifiedEvent = EventFactory::createCharacterFinesseModifedEvent(
                        $this->ControllerId,
                        $this->Id,
                        $this->ModifiedFinesse,
                        $this->ModifiedFinesse - 1,
                        $this->getInjectCode()
                    );
                    $event->theah->queueEvent($modifiedEvent);
                }
            }
        }
    }
}
