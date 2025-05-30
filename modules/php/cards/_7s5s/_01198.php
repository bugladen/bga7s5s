<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01198;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01198 extends CityAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Guild Triskelion';
        $this->Image = "img/cards/7s5s/198.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 198;
        
        $this->CityCardNumber = 22;
        $this->WealthCost = 2;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Bureaucracy',
            'Trinket',
        ];

        $this->Actions = [
            new Action_01198(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->Traits[] = 'Duelist';
            $character->IsUpdated = true;

            $event->theah->game->notifyAllPlayers('traitAdded', clienttranslate('Guild Triskelion effect triggers: ${character_name} gains the <strong>Duelist</strong> trait.'), [
                'i18n' => ['character_name'],
                'character_name' => $character->Name,
                'characterId' => $character->Id,
                'trait' => 'Duelist',
            ]);
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->Traits = array_diff($character->Traits, ['Duelist']);
            $character->IsUpdated = true;

            $event->theah->game->notifyAllPlayers('traitRemoved', clienttranslate('Guild Triskelion effect triggers: ${character_name} loses the <strong>Duelist</strong> trait.'), [
                'i18n' => ['character_name'],
                'character_name' => $character->Name,
                'characterId' => $character->Id,
                'trait' => 'Duelist',
            ]);
        }
    }
    
}