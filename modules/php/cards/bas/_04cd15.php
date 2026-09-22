<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd15;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _04cd15 extends CityAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Syrneth Puzzle Box');
        $this->Title = clienttranslate('Ponderous Prism');
        $this->Image = '04cd15.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->InPlayXImageOffset = 5;

        $this->CityCardNumber = 15;

        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            clienttranslate('Syrneth'),
            clienttranslate('Artifact'),
            clienttranslate('Trinket'),
            clienttranslate('Unique')
        ];

        $this->Text = clienttranslate("<p>Equipped character gains <b>Sorcerer</b>.</p>
<p><b>City Action:</b> Engage this card • Look at the top three cards of your deck. Sink any and replace the rest in any order. Then, you may discard a card to draw a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04cd15(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Passive grant — must remove on unequip or Sorcerer leaks (_02047 / _01198).
        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->addTrait($event->theah->game, "Sorcerer");
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->removeTrait($event->theah->game, "Sorcerer");
        }
    }
}
