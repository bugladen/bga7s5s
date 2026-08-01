<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskAttachmentTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class _04008_Silence extends Attachment implements IRiskAttachment
{
    use RiskAttachmentTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Fate's Silence");
        $this->Image = "04008.jpg";

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Sorte'),
            clienttranslate('Unique'),
        ];

        $this->ShowStatModifiers = false;
        $this->FakeAttachment = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY condition: blanking must survive if this FakeAttachment leaves theah mid-resolve;
        // Character ability gates read the condition, not attachment presence.
        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null)
            {
                $character->addCondition(Game::FATES_SILENCE_CONDITION);
                $event->theah->game->updateCardObjectInDb($character);

                $event->theah->game->notify->all("fatesSilenceConditionStarted", clienttranslate('${silence_inject_code}: ${character_inject_code} treats their text box as blank.'), [
                    "silence_inject_code" => $this->getInjectCode(),
                    "character_inject_code" => $character->getInjectCode(),
                    "cardId" => $character->Id,
                ]);
            }
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null && $character->hasCondition(Game::FATES_SILENCE_CONDITION))
            {
                $character->removeCondition(Game::FATES_SILENCE_CONDITION);
                $event->theah->game->updateCardObjectInDb($character);

                $event->theah->game->notify->all("fatesSilenceConditionEnded", clienttranslate('${character_inject_code} no longer treats their text box as blank.'), [
                    "character_inject_code" => $character->getInjectCode(),
                    "cardId" => $character->Id,
                ]);
            }
        }

        // Forced: At the end of High Drama, if this card is equipped • Destroy it.
        if ($event instanceof EventHighDramaPhaseEnd && $this->isAttached())
        {
            $this->removeRiskAttachment($event->theah);
        }
    }
}
