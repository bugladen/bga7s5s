<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01174 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Destroy Non-Unique Attachment");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $cards = array_filter($theah->getCardsInPlay(), fn($card) => $card instanceof Attachment && ! $card->hasTrait('Unique'));

        return count($cards) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01174", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01174)
        {
            $attachments = array_filter($game->theah->getCardsInPlay(), fn($card) => $card instanceof Attachment && ! $card->hasTrait('Unique'));
            $availableAttachments = [];
            foreach ($attachments as $attachment)
            {
                $availableAttachments[] = ['id' => $attachment->Id, 'name' => $attachment->Name];
            }
            
            $args["attachments"] = $availableAttachments;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01174)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if (! $attachment)
            {
                throw new \BgaUserException($game->translate("Invalid attachment"));
            }

            if (!$attachment->isControlled())
            {
                throw new \BgaUserException($game->translate("Attachment is not in play"));
            }

            if ($attachment->Location == Game::LOCATION_HAND)
            {
                throw new \BgaUserException($game->translate("Attachment is in hand"));
            }

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($attachment->ControllerId, $attachment->AttachedToId, $attachment->Id);
            $game->theah->eventCheck($unequipEvent);
            $game->theah->queueEvent($unequipEvent);

            $owner = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->ControllerId, $attachment->Id, $attachment->Location, $owner->Id, $asEffect = true);
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();
        }
    }
}
