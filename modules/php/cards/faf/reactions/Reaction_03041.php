<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03041 extends CardReaction
{
    // WHY: Capture claim-time location — by the time the player clicks, board state may have shifted.
    private string $location = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Renown From Claimed Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        $locationName = $this->location !== ''
            ? $theah->game->translate($this->location)
            : $theah->game->translate('the claimed location');

        return $base . sprintf($theah->game->translate(
            '${you} may move a Renown from %s to a different location: '
        ), $locationName);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        foreach ($theah->getCityLocations() as $cityLocation)
        {
            if ($cityLocation->Name == $this->location)
            {
                continue;
            }

            $array[] = $this->createButtonProperty(
                $theah->game,
                sprintf($theah->game->translate('To %s'), $theah->game->translate($cityLocation->Name)),
                "moveTo-{$cityLocation->Name}"
            );
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventLocationClaimed && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner == null)
            {
                return;
            }

            if ($event->playerId != $owner->ControllerId)
            {
                return;
            }

            $claimed = $event->theah->getCityLocation($event->location);
            if ($claimed == null || $claimed->Renown < 1)
            {
                return;
            }

            $hasDestination = false;
            foreach ($event->theah->getCityLocations() as $cityLocation)
            {
                if ($cityLocation->Name != $event->location)
                {
                    $hasDestination = true;
                    break;
                }
            }
            if (! $hasDestination)
            {
                return;
            }

            $this->location = $event->location;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId != 'pass' && str_starts_with($reactionId, 'moveTo-') && $this->location != '')
        {
            $destination = substr($reactionId, strlen('moveTo-'));
            $source = $game->theah->getCityLocation($this->location);
            $dest = $game->theah->getCityLocation($destination);

            if ($source !== null && $dest !== null && $destination != $this->location && $source->Renown > 0)
            {
                $batchId = $game->getNextEventBatchId();

                $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
                    $owner->ControllerId,
                    $this->location,
                    $destination,
                    1,
                    $owner->getInjectCode()
                );
                $movingEvent->batchId = $batchId;
                $game->theah->eventCheck($movingEvent);
                $game->theah->queueEvent($movingEvent);

                $removedEvent = EventFactory::createRenownRemovedFromLocationEvent(
                    $owner->ControllerId,
                    $this->location,
                    1,
                    $owner->getInjectCode()
                );
                $removedEvent->batchId = $batchId;
                $game->theah->eventCheck($removedEvent);
                $game->theah->queueEvent($removedEvent);

                $addedEvent = EventFactory::createRenownAddedToLocationEvent(
                    $owner->ControllerId,
                    $destination,
                    1,
                    $owner->getInjectCode(),
                    $isMove = true
                );
                $addedEvent->batchId = $batchId;
                $game->theah->eventCheck($addedEvent);
                $game->theah->queueEvent($addedEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to move a Renown from ${from_location} to ${to_location}.'), [
                    "i18n" => ["from_location", "to_location"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "from_location" => $this->location,
                    "to_location" => $destination,
                ]);

                $this->setUsed($game->theah, true);
            }
        }

        $this->location = '';
        $owner->IsUpdated = true;

        $game->gamestate->nextState("done");
    }
}
