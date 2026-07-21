<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _03043 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("El Gato's Mask");
        $this->Image = '03043.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 43;

        $this->initializeFaction('Castille');

        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 1;

        $this->InPlayXImageOffset = -20;

        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Attire'),
            clienttranslate('Mask'),
            clienttranslate('Unique')
        ];

        $this->Text = clienttranslate("<p>Equipped character gains <b>Scoundrel</b>.</p>
<p><b>Gambling Technique:</b> If your participant has greater [Finesse] than the adversary • Reveal two random cards from their hand. They choose and discard one revealed attachment. <i>(If any were attachments.)</i></p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_03043(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->addTrait($event->theah->game, "Scoundrel");
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->removeTrait($event->theah->game, "Scoundrel");
        }
    }
}
