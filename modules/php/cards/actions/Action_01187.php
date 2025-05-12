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
        
        $this->Name = "Smuggled Item: Equip No-Cost Attachment";
        $this->ShortName = "Equip No-Cost Attachment";
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
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $performer = $this->getOwningCharacter($event->theah);
            $event->theah->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
            $event->theah->game->globals->set(Game::EQUIP_TYPE, Game::SMUGGLED_ITEM_EQUIP_TYPE);

            $smuggledItem = $this->getOwningCard($event->theah);

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${player_name} is performing the Action from <strong>${card_name}</strong>.'), [
                'i18n' => ['card_name', 'location'],
                "player_name" => $event->theah->game->getPlayerNameById($event->playerId),
                "card_name" => $smuggledItem->Name,
            ]);

            $unattached = EventFactory::createAttachmentUnequippedEvent($event->playerId, $performer->Id, $smuggledItem->Id);
            $event->theah->queueEvent($unattached);

            $discard = EventFactory::createCardAddedToCityDiscardPileEvent($smuggledItem->ControllerId, $smuggledItem->Id, $smuggledItem->Location);
            $event->theah->queueEvent($discard);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01187");
            $event->theah->queueEvent($transition);
        }
    }

}