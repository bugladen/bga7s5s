<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _03065 extends FactionAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Lodestone");
        $this->Image = '03065.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 65;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Trinket'),
            clienttranslate('Compass')
        ];

        $this->Text = clienttranslate("<p>Opponent's abilities cannot move the equipped character <b>Home</b>.</p>
<p><b>City Action:</b> Sink this card • Move your performer <b>Home</b>.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03065(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY condition: same source-of-truth pattern as Harpoon (_03064). Stamp on the
        // equipped character so Character::eventCheck can gate opponent Home moves even
        // if Lodestone leaves $theah->cards mid-resolve; tooltip shows the restriction.
        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null)
            {
                $character->addCondition(Game::LODESTONE_CONDITION);
                $event->theah->game->updateCardObjectInDb($character);

                $event->theah->game->notify->all("lodestoneConditionStarted", clienttranslate('${character_inject_code} is protected by Lodestone: opponents cannot move them Home.'), [
                    "character_inject_code" => $character->getInjectCode(),
                    "cardId" => $character->Id,
                ]);
            }
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null && $character->hasCondition(Game::LODESTONE_CONDITION))
            {
                $character->removeCondition(Game::LODESTONE_CONDITION);
                $event->theah->game->updateCardObjectInDb($character);

                $event->theah->game->notify->all("lodestoneConditionEnded", clienttranslate('${character_inject_code} is no longer protected by Lodestone.'), [
                    "character_inject_code" => $character->getInjectCode(),
                    "cardId" => $character->Id,
                ]);
            }
        }
    }
}
