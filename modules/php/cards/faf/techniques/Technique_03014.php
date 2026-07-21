<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03014 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound the Adversary (Kaspar has an Eisenfaust)");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return $this->hasEisenfaust($theah, $owner);
    }

    private function hasEisenfaust(Theah $theah, Character $owner): bool
    {
        foreach ($owner->Attachments as $attachmentId)
        {
            $attachment = $theah->getCardById($attachmentId);
            if ($attachment !== null && $attachment->hasTrait("Eisenfaust"))
            {
                return true;
            }
        }

        $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
        foreach ($cards as $card)
        {
            if ($card->hasTrait("Eisenfaust"))
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

         // EventTechniqueCanceled handler not needed

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);

            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary !== null)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundEvent);

                $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] wounds the adversary."), $owner->getInjectCode(), $this->Name);
            }
        }
    }
}
