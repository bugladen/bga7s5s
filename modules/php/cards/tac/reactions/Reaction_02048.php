<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02048 extends RiskReaction
{
    private int $RiskSourceId = 0;
    private int $PerformerId = 0;
    private string $Location = '';
    private bool $PressureSucceeded = false;
    private bool $skipNextEvent = false;

    private ?EventCardEngaged $engagedEvent = null;
    private ?EventCardMoved $movedEvent = null;
    private ?EventCharacterBeingWounded $woundedEvent = null;
    private ?EventChallengeIssued $challengeEvent = null;
    private ?EventAttachmentEquipping $attachmentEquippingEvent = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure with Combat to Cancel Risk Effects");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose a performer to pressure with Combat and cancel the effects of the opponent\'s risk: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersAtLocationByPlayerId($this->Location, $owner->ControllerId);
        foreach ($characters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Pressure %s with %s'), $this->Location, $character->Name), "pressure-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    private function isFromOpponentRiskThatTargetsCharacters(Event $event, ?int $sourceId, int $ownerId): bool
    {
        if ($sourceId === null || $sourceId == 0)
        {
            return false;
        }

        $source = $event->theah->getCardById($sourceId);
        return $source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters && $source->ControllerId != $ownerId;
    }

    private function triggerReaction(Event $event, int $targetCharacterId): void
    {
        $owner = $this->getOwningCard($event->theah);
        $target = $event->theah->getCharacterById($targetCharacterId);
        $this->Location = $target->Location;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Auto-cancel subsequent events from a risk we already successfully pressured
        if ($this->PressureSucceeded && $this->RiskSourceId != 0)
        {
            if (($event instanceof EventCardEngaged && $event->sourceId == $this->RiskSourceId) ||
                ($event instanceof EventCardMoved && $event->sourceId == $this->RiskSourceId) ||
                ($event instanceof EventCharacterBeingWounded && $event->sourceId == $this->RiskSourceId) ||
                ($event instanceof EventChallengeIssued && $event->sourceId == $this->RiskSourceId) ||
                ($event instanceof EventAttachmentEquipping && $event->sourceId == $this->RiskSourceId))
            {
                if ($event instanceof EventAttachmentEquipping)
                {
                    $this->discardAttachment($event, $event->playerId, $event->attachmentId);
                }
                $event->canceled = true;

                if ($event->batchId)
                {
                    $event->theah->deleteEventBatch($event->batchId);
                }

                return;
            }
        }

        // Trigger on individual effect events from opponent's risk targeting my character
        // Following the pattern from _01186 (Maryam Benu Pleroma)
        if ($this->skipNextEvent &&
            ($event instanceof EventCardEngaged || $event instanceof EventCardMoved ||
             $event instanceof EventCharacterBeingWounded || $event instanceof EventChallengeIssued ||
             $event instanceof EventAttachmentEquipping))
        {
            $this->skipNextEvent = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
            return;
        }

        if ($event instanceof EventCardEngaged && !$event->canceled && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $target = $event->theah->getCharacterById($event->cardId);
                if ($target && $target->ControllerId == $owner->ControllerId
                    && $this->isFromOpponentRiskThatTargetsCharacters($event, $event->sourceId, $owner->ControllerId))
                {
                    $this->RiskSourceId = $event->sourceId;
                    $this->engagedEvent = clone $event;
                    unset($this->engagedEvent->theah);
                    $event->canceled = true;

                    if ($event->batchId)
                    {
                        $event->theah->deleteEventBatch($event->batchId);
                    }

                    $this->triggerReaction($event, $event->cardId);
                }
            }
        }

        if ($event instanceof EventCardMoved && !$event->canceled && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $target = $event->theah->getCharacterById($event->cardId);
                if ($target && $target->ControllerId == $owner->ControllerId
                    && $this->isFromOpponentRiskThatTargetsCharacters($event, $event->sourceId, $owner->ControllerId))
                {
                    $this->RiskSourceId = $event->sourceId;
                    $this->movedEvent = clone $event;
                    unset($this->movedEvent->theah);
                    $event->canceled = true;

                    if ($event->batchId)
                    {
                        $event->theah->deleteEventBatch($event->batchId);
                    }

                    $this->triggerReaction($event, $event->cardId);
                }
            }
        }

        if ($event instanceof EventCharacterBeingWounded && !$event->canceled && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $target = $event->theah->getCharacterById($event->characterId);
                if ($target && $target->ControllerId == $owner->ControllerId
                    && $this->isFromOpponentRiskThatTargetsCharacters($event, $event->sourceId, $owner->ControllerId))
                {
                    $this->RiskSourceId = $event->sourceId;
                    $this->woundedEvent = clone $event;
                    unset($this->woundedEvent->theah);
                    $event->canceled = true;

                    if ($event->batchId)
                    {
                        $event->theah->deleteEventBatch($event->batchId);
                    }

                    $this->triggerReaction($event, $event->characterId);
                }
            }
        }

        if ($event instanceof EventChallengeIssued && !$event->canceled && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $defender = $event->theah->getCharacterById($event->defenderId);
                if ($defender && $defender->ControllerId == $owner->ControllerId
                    && $this->isFromOpponentRiskThatTargetsCharacters($event, $event->sourceId, $owner->ControllerId))
                {
                    $this->RiskSourceId = $event->sourceId;
                    $this->challengeEvent = clone $event;
                    unset($this->challengeEvent->theah);
                    $event->canceled = true;

                    if ($event->batchId)
                    {
                        $event->theah->deleteEventBatch($event->batchId);
                    }

                    $this->triggerReaction($event, $event->defenderId);
                }
            }
        }

        if ($event instanceof EventAttachmentEquipping && !$event->canceled && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND && $event->sourceId !== null)
            {
                $target = $event->theah->getCharacterById($event->characterId);
                if ($target && $target->ControllerId == $owner->ControllerId
                    && $this->isFromOpponentRiskThatTargetsCharacters($event, $event->sourceId, $owner->ControllerId))
                {
                    $this->RiskSourceId = $event->sourceId;
                    $this->attachmentEquippingEvent = clone $event;
                    unset($this->attachmentEquippingEvent->theah);
                    $event->canceled = true;

                    if ($event->batchId)
                    {
                        $event->theah->deleteEventBatch($event->batchId);
                    }

                    $this->triggerReaction($event, $event->characterId);
                }
            }
        }

        // Initiate pressure
        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performer = $event->theah->getCharacterById($this->PerformerId);

            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $pressureStats = $game->theah->getPressureStats($performer, $this->Location, Game::STAT_COMBAT);
            $pressureEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $performer->Id, $this->Location, $pressureStats);
            $game->theah->queueEvent($pressureEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used the Reaction to Pressure ${location_name} with Combat'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'location_name' => $this->Location,
            ]);

            [$success, $totals, $difference] = $game->pressureLocation($owner->ControllerId, $performer, $this->Location, Game::STAT_COMBAT);

            $pressuredEvent = EventFactory::createLocationPressuredEvent($owner->ControllerId, $performer->Id, $this->Location, Game::STAT_COMBAT, $success, $totals, $difference);
            $pressuredEvent->abilityId = $this->Id;
            $game->theah->queueEvent($pressuredEvent);

            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
        }

        // Handle pressure outcome
        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);

            if ($event->success)
            {
                $this->PressureSucceeded = true;

                if ($this->attachmentEquippingEvent)
                {
                    $this->discardAttachment($event, $this->attachmentEquippingEvent->playerId, $this->attachmentEquippingEvent->attachmentId);
                }

                $this->clearSavedEvents();

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: Pressure successful! The effects of the opponent\'s risk have been canceled.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                ]);
            }
            else
            {
                $this->reEmitSavedEvent($game);
            }

            $owner->IsUpdated = true;
        }

        // Cleanup
        if ($event instanceof EventPlayerTurnEnd && ($this->PressureSucceeded || $this->RiskSourceId != 0))
        {
            $owner = $this->getOwningCard($event->theah);
            $this->RiskSourceId = 0;
            $this->PerformerId = 0;
            $this->Location = '';
            $this->PressureSucceeded = false;
            $this->clearSavedEvents();
            $owner->IsUpdated = true;
        }
    }

    private function discardAttachment(Event $event, int $playerId, int $attachmentId): void
    {
        $attachment = $event->theah->getCardById($attachmentId);

        $removedEvent = EventFactory::createCardRemovedFromPlayEvent($playerId, $attachment->Id, $attachment->Location);
        $event->queueEvent($removedEvent);

        if ($attachment instanceof CityAttachment)
        {
            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $attachment->Id, $attachment->Location);
            $event->queueEvent($discardEvent);
        }
        else
        {
            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location);
            $event->queueEvent($discardEvent);
        }
    }

    private function clearSavedEvents(): void
    {
        $this->engagedEvent = null;
        $this->movedEvent = null;
        $this->woundedEvent = null;
        $this->challengeEvent = null;
        $this->attachmentEquippingEvent = null;
    }

    private function reEmitSavedEvent(Game $game): void
    {
        if ($this->engagedEvent)
        {
            $game->theah->queueEvent($this->engagedEvent);
            $this->engagedEvent = null;
        }

        if ($this->movedEvent)
        {
            $game->theah->queueEvent($this->movedEvent);
            $this->movedEvent = null;
        }

        if ($this->woundedEvent)
        {
            $game->theah->queueEvent($this->woundedEvent);
            $this->woundedEvent = null;
        }

        if ($this->challengeEvent)
        {
            $game->theah->queueEvent($this->challengeEvent);
            $this->challengeEvent = null;
        }

        if ($this->attachmentEquippingEvent)
        {
            $game->theah->queueEvent($this->attachmentEquippingEvent);
            $this->attachmentEquippingEvent = null;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'decline')
        {
            $characterId = (int) str_replace("pressure-", "", $reactionId);
            $this->PerformerId = $characterId;

            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }
        else
        {
            $this->skipNextEvent = true;
            $this->reEmitSavedEvent($game);
            $this->RiskSourceId = 0;
            $this->Location = '';

            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
