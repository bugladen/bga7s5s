<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01187 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = "Equip No-Cost Attachment";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $smuggledItem = $this->getOwningCard($theah);
        $attachmentsInHand = $theah->game->getAttachmentsInHand($smuggledItem->ControllerId);
        $attachmentsInPlay = $theah->getAvailableAttachmentsAtLocation($smuggledItem->Location);

        return count($attachmentsInHand) > 0 || count($attachmentsInPlay) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $performer = $this->getOwningCharacter($event->theah);
            $event->theah->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
            $event->theah->game->globals->set(Game::EQUIP_TYPE, Game::SMUGGLED_ITEM_EQUIP_TYPE);

            $smuggledItem = $this->getOwningCard($event->theah);

            $event->theah->game->globals->set(Game::SMUGGLED_ITEM_ATTACHMENT_ID, $smuggledItem->Id);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01187");
            $event->theah->queueEvent($transition);
        }
    }

}