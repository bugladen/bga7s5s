<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04056a extends RiskReaction implements ICancelReaction
{
    // WHY public: pay / serialize round-trips (Pattern D.3 / D.2).
    public int $batchId = 0;
    public string $fromLocation = '';
    public string $toLocation = '';
    public int $amount = 0;
    public int $performerId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Renown Movement To or From Performer's Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . sprintf(
            $theah->game->translate('${you} may cancel the movement of %d Renown from %s to %s: '),
            $this->amount,
            $theah->game->translate($this->fromLocation),
            $theah->game->translate($this->toLocation)
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Movement'), 'cancel');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventRenownMovingBetweenLocations && $this->isAvailable())
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

            if ($event->amount <= 0)
            {
                return;
            }

            // "to or from your performer's location" — any controlled character at either end.
            $performer = $this->findPerformerAtLocation($event->theah, $owner->ControllerId, $event->fromLocation);
            if ($performer === null)
            {
                $performer = $this->findPerformerAtLocation($event->theah, $owner->ControllerId, $event->toLocation);
            }
            if ($performer === null)
            {
                return;
            }

            $this->batchId = $event->batchId ?? 0;
            $this->fromLocation = $event->fromLocation;
            $this->toLocation = $event->toLocation;
            $this->amount = $event->amount;
            $this->performerId = $performer->Id;
            $owner->IsUpdated = true;

            // WHY stackEvent: Added/Removed sit at HIGH_PRIORITY; a queued reaction transition
            // at REACTION_PRIORITY would run after the Renown already moved (Pattern D.3).
            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }

            // EventRenownMovingBetweenLocations is informational — delete the Add/Remove pair.
            if ($this->batchId > 0)
            {
                $event->theah->deleteRenownRemovedFromLocationEventsByBatchId($this->batchId);
                $event->theah->deleteRenownAddedToLocationEventsByBatchId($this->batchId);
            }

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} cancelled the movement of ${amount} Renown from ${from_location} to ${to_location}.'), [
                "i18n" => ["from_location", "to_location"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "amount" => $this->amount,
                "from_location" => $this->fromLocation,
                "to_location" => $this->toLocation,
            ]);

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

        if ($reactionId === 'cancel')
        {
            // Stack pay ahead of pending Add/Remove (Pattern D.3 LIFO).
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

    private function findPerformerAtLocation(Theah $theah, int $playerId, string $location): ?Character
    {
        if ($location === '' || $location === Game::LOCATION_PLAYER_HOME)
        {
            return null;
        }

        $characters = $theah->getCharactersAtLocationByPlayerId($location, $playerId);
        foreach ($characters as $character)
        {
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
        $this->batchId = 0;
        $this->fromLocation = '';
        $this->toLocation = '';
        $this->amount = 0;
        $this->performerId = 0;
    }
}
