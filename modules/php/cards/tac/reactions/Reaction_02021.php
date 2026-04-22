<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02021 extends CardReaction
{
    private int $attachmentId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('One of your attachments discarded to pay a cost gains Wealth.');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . clienttranslate('Choose an attachment in your hand to gain Wealth:');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        $attachments = $theah->game->getAttachmentsInHand($owner->ControllerId);
        foreach ($attachments as $attachment)
        {
            $array[] = $this->createButtonProperty($theah->game, "Convert to Wealth: $attachment->Name", "convertToWealth-$attachment->Id");
        }
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventEnteringPayState && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner && $event->playerId == $owner->ControllerId && $event->theah->cardInCity($owner))
            {
                $attachments = $event->theah->game->getAttachmentsInHand($event->playerId);
                if (count($attachments) > 0)
                {
                    $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transitionEvent);
                }
            }
        }

        if ($event instanceof EventCardDiscardedFromHand && $event->AsPayment && $this->attachmentId != 0 && $event->cardId == $this->attachmentId)
        {
            $attachment = $event->theah->getAttachmentById($this->attachmentId);
            if ($attachment)
            {
                $attachment->removeTrait($event->theah->game, 'Wealth');
            }

            $lockerEvent = EventFactory::createCardSentToLockerEvent($event->ownerId, $event->cardId);
            $event->theah->queueEvent($lockerEvent);

            $this->attachmentId = 0;
            $owner = $this->getOwningCard($event->theah);
            if ($owner) $owner->IsUpdated = true;
        }

        if ($event instanceof EventPlayerTurnEnd && $this->attachmentId != 0)
        {
            $attachment = $event->theah->getAttachmentById($this->attachmentId);
            if ($attachment && $attachment->Location == Game::LOCATION_HAND)
            {
                $attachment->removeTrait($event->theah->game, 'Wealth');
            }
            $this->attachmentId = 0;
            $owner = $this->getOwningCard($event->theah);
            if ($owner) $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass' && $reactionId != 'decline')
        {
            $cardId = (int) str_replace("convertToWealth-", "", $reactionId);
            $attachment = $game->theah->getAttachmentById($cardId);
            if ($attachment)
            {
                $attachment->addTrait($game, 'Wealth');
                $this->attachmentId = $cardId;
                $owner = $this->getOwningCard($game->theah);
                if ($owner) $owner->IsUpdated = true;
            }
        }

        $this->setUsed($game->theah, true);
        $game->gamestate->nextState("done");
    }
}
