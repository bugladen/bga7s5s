<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01181 extends AttachmentReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Id = 'Reaction_01181';
    }

    public function getButtonProperties(Theah $theah): array
    {
        $array = [];
        $array[] = $this->createButtonProperty('Heal 1 Wound', 'heal1Wound');

        $owner = $this->getOwningCharacter($theah);
        if ($owner instanceof Character)
        {
            if ($owner && in_array("Strega", $owner->Traits) && $owner->Wounds > 1)
                $array[] = $this->createButtonProperty('Heal 2 Wounds', 'heal2Wounds');    
        }

        $array[] = $this->createButtonProperty('Pass', 'pass');

        return $array;
    }

    public function getStateDescription(Theah $theah): string
    {
        $owner = $this->getOwningCharacter($theah);
        return $owner->Name . ' > Sorte Deck > Reaction: ${you} may choose to Heal Wounds: ';
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        $attachment = $this->getOwningCard($event->theah);
        if ($event instanceof EventCharacterWounded && $this->ownerIsAttached($event->theah) && $this->isAvailable() && ! $attachment->Engaged)
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source->Location == $attachment->Location) {
                $this->Used = true;
                $attachment->IsUpdated = true;

                $transition = $event->theah->createEvent(Events::Transition);
                if ($transition instanceof EventTransition)
                {
                    $transition->transition = 'reaction';
                    $transition->playerId = $attachment->ControllerId;
                    $transition->sourceId = $attachment->Id;
                    $transition->internalId = $this->Id;
                    $transition->priority = Event::LOWEST_PRIORITY;

                }
                $event->theah->queueEvent($transition);
            }   
        }

    }
}