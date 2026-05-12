<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class _03cd21 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Silver Spine');
        $this->Image = '03cd21.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;
        $this->CityCardNumber = 21;

        $this->WealthCost = 2;
        $this->ResolveModifier = 1;

        $this->Traits = [
            clienttranslate('Artifact'),
            clienttranslate('Syrneth'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<b>Forced:</b> Each Day, the first time an opponent's Risk targets the equipped character • Cancel the effects.");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        // WHY: gate on isAttached() because the cancel only fires while the artifact
        // is equipped to a character. Source-id checks mirror _01186 (Maryam) — the
        // event must have originated from a card (sourceId != 0) and that card must
        // be a Risk that targets characters, controlled by someone other than the
        // equipped character's controller ("an opponent's Risk").

        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            (($event instanceof EventCardMoved && $event->cardId == $this->AttachedToId && $event->sourceId != 0) ||
            ($event instanceof EventCardEngaged && $event->cardId == $this->AttachedToId && $event->sourceId != 0))
        )
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->markAbilityUsed($event->theah->game);
                $event->canceled = true;
                return;
            }
        }

        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            $event instanceof EventChallengeIssued && $event->defenderId == $this->AttachedToId && $event->sourceId != 0)
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->markAbilityUsed($event->theah->game);
                $event->canceled = true;
                return;
            }
        }

        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            $event instanceof EventCharacterBeingWounded && $event->characterId == $this->AttachedToId && $event->sourceId != 0)
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->markAbilityUsed($event->theah->game);
                $event->canceled = true;
                return;
            }
        }

        if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
            $event instanceof EventAttachmentEquipping && $event->characterId == $this->AttachedToId && $event->sourceId != 0)
        {
            if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId))
            {
                $this->markAbilityUsed($event->theah->game);

                // WHY discard the would-be attachment: EventAttachmentEquipping was canceled,
                // so EventAttachmentEquipped never fires to place the card. Without this the
                // attachment is left in limbo. Same shape as _01186's handler — CityAttachment
                // routes to city discard, faction attachments to owner discard.
                $attachment = $event->theah->getCardById($event->attachmentId);
                if ($attachment)
                {
                    $removedEvent = EventFactory::createCardRemovedFromPlayEvent($event->playerId, $attachment->Id, $attachment->Location);
                    $event->theah->queueEvent($removedEvent);

                    if ($attachment instanceof CityAttachment)
                    {
                        $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($event->playerId, $attachment->Id, $attachment->Location);
                        $event->queueEvent($discardEvent);
                    }
                    else
                    {
                        $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location);
                        $event->queueEvent($discardEvent);
                    }
                }

                $event->canceled = true;
                return;
            }
        }

        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay && $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED))
        {
            $this->removeCondition(Game::SILVER_SPINE_ABILITY_USED);
            $event->theah->game->notify->all("silverSpineAbilityRemoved", "", [
                "cardId" => $this->Id,
            ]);
        }
    }

    private function isOpponentRiskTargetingCharacters(Event $event, int $sourceId): bool
    {
        $source = $event->theah->getCardById($sourceId);
        return $source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters
            && $source->ControllerId != $this->ControllerId;
    }

    private function markAbilityUsed(Game $game): void
    {
        $this->addCondition(Game::SILVER_SPINE_ABILITY_USED);
        $game->notify->all("silverSpineAbilityUsed", clienttranslate('${card_inject_code} cancels the effects of the opposing Risk.'), [
            "cardId" => $this->Id,
            "card_inject_code" => $this->getInjectCode(),
        ]);
    }
}
