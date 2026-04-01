<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _02027 extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate('Éventail');
        $this->Image = "02027.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 27;
        $this->initializeFaction('Montaigne');

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Traits = [
            clienttranslate('Attire'),
            clienttranslate('Fan'),
        ];

        $this->Text = clienttranslate("<p>May only equip to your <b>Diplomat</b>.</p><p>While equipped character is en garde, they gain +1[influence]. <i>(While performing the Parley Reaction or the Claim Action, engage costs occur first.)</i></p>");
        $this->resetCard();
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if (! $character->hasTrait("Diplomat"))
            {
                throw new UserException($event->theah->game->translate("Éventail can only be equipped to a Diplomat."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $character->hasTrait("Diplomat");
    }

    private function modifyInfluence(Theah $theah, Character $character, int $amount): void
    {
        $oldInfluence = $character->ModifiedInfluence;
        $character->ModifiedInfluence = max(0, $character->ModifiedInfluence + $amount);
        $character->IsUpdated = true;

        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $character->ControllerId,
            $character->Id,
            $oldInfluence,
            $character->ModifiedInfluence,
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character && ! $character->Engaged)
            {
                $this->modifyInfluence($event->theah, $character, 1);
            }
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character && ! $character->Engaged)
            {
                $this->modifyInfluence($event->theah, $character, -1);
            }
        }

        if ($event instanceof EventCardEngaged && $this->isAttached() && $event->cardId == $this->AttachedToId)
        {
            $character = $event->theah->getCharacterById($event->cardId);
            if ($character)
            {
                $this->modifyInfluence($event->theah, $character, -1);
            }
        }

        if ($event instanceof EventCardEngarded && $this->isAttached() && $event->cardId == $this->AttachedToId)
        {
            $character = $event->theah->getCharacterById($event->cardId);
            if ($character)
            {
                $this->modifyInfluence($event->theah, $character, 1);
            }
        }
    }
}