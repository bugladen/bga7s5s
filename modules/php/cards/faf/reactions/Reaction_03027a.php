<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03027a extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Heal a wound; optionally move adjacent Renown to Odette's location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may heal Odette and optionally move an adjacent Renown to this location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            foreach ($this->getRenownSources($theah, $owner) as $locationName)
            {
                $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Heal and move Renown from %s'), $locationName), "moveFrom-$locationName");
            }
            if ($owner->Wounds > 0)
            {
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Heal only'), 'healOnly');
            }
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        }

        return $array;
    }

    /**
     * @return string[] adjacent city locations that currently have at least 1 Renown.
     */
    private function getRenownSources(Theah $theah, Character $owner): array
    {
        $sources = [];
        $adjacent = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        foreach ($adjacent as $locationName)
        {
            $location = $theah->getCityLocation($locationName);
            if ($location !== null && $location->Renown > 0)
            {
                $sources[] = $locationName;
            }
        }
        return $sources;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCharacterDestroyed)) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;
        if ($event->theah->game->characterIsInDiscardOrLocker($owner)) return;
        if (! $event->theah->cardInCity($owner)) return;

        if ($event->characterId == $owner->Id) return;

        $destroyed = $event->theah->getCharacterById($event->characterId);
        if ($destroyed === null) return;
        if ($destroyed->Location != $owner->Location) return;

        $canHeal = $owner->Wounds > 0;
        $hasRenownSource = count($this->getRenownSources($event->theah, $owner)) > 0;
        if (! $canHeal && ! $hasRenownSource) return;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'pass')
        {
            $game->gamestate->nextState("done");
            return;
        }

        if ($owner->Wounds > 0)
        {
            $healEvent = EventFactory::createCharacterBeingHealedEvent(
                $owner->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($healEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${character_inject_code} heals a wound.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "character_inject_code" => $owner->getInjectCode(),
            ]);
        }

        if (str_starts_with($reactionId, 'moveFrom-'))
        {
            $locationName = substr($reactionId, strlen('moveFrom-'));

            $adjacent = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            $location = $game->theah->getCityLocation($locationName);
            if (in_array($locationName, $adjacent) && $location !== null && $location->Renown > 0)
            {
                $batchId = $game->getNextEventBatchId();

                $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent($owner->ControllerId, $locationName, $owner->Location, 1, $owner->getInjectCode());
                $movingEvent->batchId = $batchId;
                $game->theah->eventCheck($movingEvent);
                $game->theah->queueEvent($movingEvent);

                $removedEvent = EventFactory::createRenownRemovedFromLocationEvent($owner->ControllerId, $locationName, 1, $owner->getInjectCode());
                $removedEvent->batchId = $batchId;
                $game->theah->eventCheck($removedEvent);
                $game->theah->queueEvent($removedEvent);

                $addedEvent = EventFactory::createRenownAddedToLocationEvent($owner->ControllerId, $owner->Location, 1, $owner->getInjectCode(), $isMove = true);
                $addedEvent->batchId = $batchId;
                $game->theah->eventCheck($addedEvent);
                $game->theah->queueEvent($addedEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} moves 1 Renown from ${location_name} to ${owner_location}.'), [
                    "i18n" => ["location_name", "owner_location"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "location_name" => $locationName,
                    "owner_location" => $owner->Location,
                ]);
            }
        }

        $this->setUsed($game->theah, true);

        $game->gamestate->nextState("done");
    }
}
