<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_01191 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound All Characters");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $duckfootPistol = $this->getOwningCard($event->theah);
            $ownerId = 0;
            if ($duckfootPistol instanceof Attachment)
            {
                $ownerId = $duckfootPistol->AttachedToId;
            }

            $owner = $event->theah->getCardById($ownerId);
            $location = $owner->Location;

            $event->theah->game->notifyAllPlayers("duckfootPistolUsed", clienttranslate('${player_name} uses ${card_name} to wound all Non-Leader characters at ${location}'), [
                'i18n' => ['card_name', 'location'],
                'player_name' => $event->theah->game->getActivePlayerName(),
                'card_name' => $duckfootPistol->Name,
                'location' => $location
            ]);

            $characters = $event->theah->getCharactersAtLocation($location);
            // Filter out characters are not leaders
            $characters = array_filter($characters, fn($character) => ! $character instanceof Leader);

            $unequippedEvent = EventFactory::createAttachmentUnequippedEvent($event->playerId, $ownerId, $duckfootPistol->Id);
            $event->theah->queueEvent($unequippedEvent);

            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($event->playerId, $duckfootPistol->Id, $location);
            $event->theah->queueEvent($discardEvent);

            foreach ($characters as $character)
            {
                $woundEvent = EventFactory::createCharacterWoundedEvent($character->Id, $duckfootPistol->Id, 1, $duckfootPistol->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($character->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);

            $this->resetPlayerPassCount($event->theah->game);
            // $this->setUsed() not called because this card is destroyed
        }
    }
}