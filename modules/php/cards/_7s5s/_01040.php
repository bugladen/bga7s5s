<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01040;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _01040 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Rena Klingenhalter");
        $this->Image = "01040.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 40;

        $this->initializeFaction("Eisen");
        $this->Title = clienttranslate("Master-at-arms");
        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Weapons Master"),
            clienttranslate("Eisen"),
        ];

        $this->Text = clienttranslate("<p>Rena may intervene by engaging her equipped Weapon instead of herself. She may do this even while engaged.</p><p>While Rena has a Weapon equipped, she gains +1 [Combat].</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01040(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

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
                    $modifiedEvent = EventFactory::createCharacterCombatModifiedEvent($this->ControllerId, $this->Id, $this->ModifiedCombat, $this->ModifiedCombat + 1, $this->getInjectCode());
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
                    $modifiedEvent = EventFactory::createCharacterCombatModifiedEvent($this->ControllerId, $this->Id, $this->ModifiedCombat, $this->ModifiedCombat - 1, $this->getInjectCode());
                    $event->theah->queueEvent($modifiedEvent);
                }
            }
        }
    }
}