<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\GameFramework\UserException;

class _01190 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Sigurd Ulfsen');
        $this->Image = "01190.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 190;
        
        $this->Title = clienttranslate('Grizzled Deathseeker');

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost = 4;
        $this->CityCardNumber = 14;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Pirate'),
            clienttranslate('Vesten'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>Sigurd's [Combat] cannot be increased and he cannot issue challenges.</p><p>While Sigurd is en garde, he must be the target of enemy challenges at this location.</p>");

        $this->resetCard();
    }

    public function canChallenge(): bool
    {
        return false;
    }

    public function addAttachment(Theah $theah, Attachment $attachment)
    {
        parent::addAttachment($theah, $attachment);

        if ($this->ModifiedCombat > $this->Combat) 
        {
            $this->ModifiedCombat = $this->Combat;
        }
    }

    public function removeAttachment(Theah $theah, Attachment $attachment)
    {
        parent::removeAttachment($theah, $attachment);

        // WHY: parent subtracts the attachment's CombatModifier, but if that modifier
        // was capped during addAttachment, the subtraction over-corrects. Recalculate
        // from base + remaining attachments to avoid phantom subtraction.
        $this->recalculateCappedCombat($theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Cap combat after EventHub processes EventCharacterCombatModified,
        // which bypasses the addAttachment path entirely
        if ($event instanceof EventCharacterCombatModified && $event->CharacterId == $this->Id)
        {
            if ($this->ModifiedCombat > $this->Combat)
            {
                $this->ModifiedCombat = $this->Combat;
                $this->IsUpdated = true;
            }
        }
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventChallengeIssued && $this->isControlled())
        {
            $defender = $event->theah->getCardById($event->defenderId);
            $challenger = $event->theah->getCardById($event->challengerId);
            if ($challenger->ControllerId != $this->ControllerId &&
                $this->Id != $event->defenderId &&
                $defender->Location == $this->Location &&
                ! $this->Engaged)
            {
                throw new UserException($event->theah->game->translate("Sigurd Ulfsen must be the target of the challenge if he is en garde and in the same location."));
            }
        }
    }

    private function recalculateCappedCombat(Theah $theah)
    {
        $combat = $this->Combat;
        foreach ($this->Attachments as $attachmentId)
        {
            $att = $theah->getAttachmentById($attachmentId);
            if (!$att)
                continue;

            $combat += $att->CombatModifier;
        }

        foreach ($this->Attachments as $attachmentId)
        {
            $att = $theah->getAttachmentById($attachmentId);
            if (!$att)
                continue;

            if ($att->CombatLocked)
            {
                $combat = $att->CombatLockedValue;
            }
        }

        $this->ModifiedCombat = max(0, min($combat, $this->Combat));
    }

}