<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTakeReknownForControlledLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04056b extends RiskReaction implements ICancelReaction
{
    // WHY public: pay / serialize round-trips while Collect events stay queued.
    public int $collectorPlayerId = 0;
    public string $location = '';
    public int $originalAmount = 0;
    public int $performerId = 0;
    // WHY: Ability Collect applies Removed before pay; Plunder still has Removed queued.
    public bool $needsPutBack = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Collect One Fewer Renown From Performer's Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . sprintf(
            $theah->game->translate('${you} may play this Risk so %s collects one fewer Renown from %s: '),
            $theah->game->getPlayerNameById($this->collectorPlayerId),
            $theah->game->translate($this->location)
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Greed'), 'use');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPlayerTakeReknownForControlledLocation && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }
            if (! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

            if ($event->reknown <= 0)
            {
                return;
            }

            $performer = $this->findEnGardePerformerAtLocation($event->theah, $owner->ControllerId, $event->location);
            if ($performer === null)
            {
                return;
            }

            $this->collectorPlayerId = $event->playerId;
            $this->location = $event->location;
            $this->originalAmount = $event->reknown;
            $this->performerId = $performer->Id;
            $this->needsPutBack = false;
            $owner->IsUpdated = true;

            // WHY stackEvent: Gains/Removed are already queued at MEDIUM; offer must pre-empt
            // them so pay can mutate amounts before they apply (same priority math as D.3).
            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventRenownRemovedFromLocation && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }
            if (! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

            // Plunder Removed often has playerId 0 — that path offers on Take instead.
            if ($event->playerId == 0 || $event->amount <= 0)
            {
                return;
            }

            // Distinguishes Collect (Removed→Gains) from Move (Removed→Added). Peek the queue.
            if (! $event->theah->hasQueuedPlayerGainsReknownForPlayer($event->playerId))
            {
                return;
            }

            $performer = $this->findEnGardePerformerAtLocation($event->theah, $owner->ControllerId, $event->location);
            if ($performer === null)
            {
                return;
            }

            $this->collectorPlayerId = $event->playerId;
            $this->location = $event->location;
            $this->originalAmount = $event->amount;
            $this->performerId = $performer->Id;
            // Removed is the current event — hub already applied full remove.
            $this->needsPutBack = true;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                $this->resetSavedState();
                return;
            }

            $gainsReduced = $event->theah->decrementFirstQueuedPlayerGainsReknown($this->collectorPlayerId, 1);
            $removedReduced = false;
            if (! $this->needsPutBack)
            {
                $removedReduced = $event->theah->decrementFirstQueuedRenownRemovedFromLocation($this->location, 1);
            }

            if ($this->needsPutBack)
            {
                // Ability Collect: Remaining stays via put-back (Removed already applied full).
                $addEvent = EventFactory::createRenownAddedToLocationEvent(
                    $owner->ControllerId,
                    $this->location,
                    1,
                    $owner->getInjectCode()
                );
                $event->theah->queueEvent($addEvent);
            }

            if ($gainsReduced || $removedReduced || $this->needsPutBack)
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} forced ${collector_name} to collect one fewer Renown from ${location_name} (which had ${original_amount} on it). Remaining Renown stays.'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "collector_name" => $game->getPlayerNameById($this->collectorPlayerId),
                    "location_name" => $this->location,
                    "original_amount" => $this->originalAmount,
                ]);
            }

            $this->setUsed($event->theah, true);
            $this->resetSavedState();
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $this->resetSavedState();
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'use')
        {
            $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->stackEvent($payTransition);

            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->stackEvent($payEvent);
        }
        else
        {
            $this->resetSavedState();
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }

    private function findEnGardePerformerAtLocation(Theah $theah, int $playerId, string $location): ?Character
    {
        if ($location === '' || $location === Game::LOCATION_PLAYER_HOME)
        {
            return null;
        }

        $characters = $theah->getCharactersAtLocationByPlayerId($location, $playerId);
        foreach ($characters as $character)
        {
            if ($character->Engaged)
            {
                continue;
            }
            if ($theah->game->characterIsInDiscardOrLocker($character))
            {
                continue;
            }
            return $character;
        }

        return null;
    }

    private function resetSavedState(): void
    {
        $this->collectorPlayerId = 0;
        $this->location = '';
        $this->originalAmount = 0;
        $this->performerId = 0;
        $this->needsPutBack = false;
    }
}
