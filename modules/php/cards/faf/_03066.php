<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class _03066 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Shackles");
        $this->Image = '03066.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 66;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->InPlayXImageOffset = -20;

        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 2;

        // WHY: Equips onto an opposing character (Legion's Caress shape). Without this
        // the HD equip performer list never includes opponents.
        $this->CanEquipToOpponents = true;

        $this->Traits = [
            clienttranslate('Chains'),
            clienttranslate('Restraint')
        ];

        $this->Text = clienttranslate("<p>This card may only equip to an opposing character that has less [Finesse] than your performer.</p>
<p>Equipped character cannot move.</p>
<p><b>Forced:</b> At the end of High Drama • Destroy this attachment.</p>");

        $this->resetCard();
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $playerId = $event->playerId;

            // Opposing = different controller (same-location half of "opposing" is
            // enforced by requiring a higher-Finesse ally at that location below).
            if ($character->ControllerId == $playerId)
            {
                throw new UserException($event->theah->game->translate("Shackles can only be equipped to an opposing character."));
            }

            // WHY: BGA Equip collapses CHOSEN_PERFORMER to the equip *target* (same as
            // Legion's Caress). Printed text still needs "your performer" with greater
            // Finesse. Resolve that as: you control a character at the target's location
            // with ModifiedFinesse > target. That also satisfies "opposing" same-location.
            $allies = $event->theah->getCharactersAtLocation($character->Location);
            $allies = array_filter(
                $allies,
                fn(Character $ally): bool =>
                    $ally->ControllerId == $playerId
                    && $ally->ModifiedFinesse > $character->ModifiedFinesse
            );

            if (count($allies) === 0)
            {
                throw new UserException($event->theah->game->translate("Shackles can only be equipped to an opposing character that has less Finesse than your performer at the same location."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        // Finesse vs same-location performer needs Theah — enforced in eventCheck.
        return $this->ControllerId > 0 && $character->ControllerId != $this->ControllerId;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY condition: same source-of-truth pattern as Harpoon / Lodestone. Stamp on the
        // equipped character so Character::eventCheck gates moves even if Shackles leaves
        // $theah->cards mid-resolve; tooltip shows the restriction. Clear on unequip
        // (while-equipped, not remainder-of-duel).
        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null)
            {
                $character->addCondition(Game::SHACKLES_CONDITION);
                $event->theah->game->updateCardObjectInDb($character);

                $event->theah->game->notify->all("shacklesConditionStarted", clienttranslate('${character_inject_code} is Shackled and cannot move.'), [
                    "character_inject_code" => $character->getInjectCode(),
                    "cardId" => $character->Id,
                ]);
            }
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character !== null && $character->hasCondition(Game::SHACKLES_CONDITION))
            {
                $character->removeCondition(Game::SHACKLES_CONDITION);
                $event->theah->game->updateCardObjectInDb($character);

                $event->theah->game->notify->all("shacklesConditionEnded", clienttranslate('${character_inject_code} is no longer Shackled.'), [
                    "character_inject_code" => $character->getInjectCode(),
                    "cardId" => $character->Id,
                ]);
            }
        }

        // Forced: At the end of High Drama • Destroy this attachment.
        // Mirror _01025_Burden trigger (EventHighDramaPhaseEnd) + _01153 destroy chain.
        if ($event instanceof EventHighDramaPhaseEnd && $this->isAttached())
        {
            $owner = $this->attachedTo($event->theah);
            if ($owner instanceof Character)
            {
                $event->theah->game->notify->all("message", clienttranslate('${attachment_inject_code}: Forced ability destroys this attachment at the end of High Drama.'), [
                    "attachment_inject_code" => $this->getInjectCode(),
                ]);

                $unequipEvent = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($unequipEvent);

                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($this->ControllerId, $this->Id, $this->Location, $this->Id, $asEffect = true);
                $event->theah->queueEvent($discardEvent);
            }
        }
    }
}
