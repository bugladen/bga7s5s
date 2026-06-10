<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03027b extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move adjacent Duelist to Odette's location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may move an adjacent Duelist to this location (before choosing to intervene): ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            foreach ($this->getEligibleDuelists($theah, $owner) as $duelist)
            {
                $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Move %s from %s'), $duelist->Name, $duelist->Location), "move-{$duelist->Id}");
            }
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        }

        return $array;
    }

    /**
     * @return Character[] Duelists controlled by the owner at adjacent city locations.
     */
    private function getEligibleDuelists(Theah $theah, Character $owner): array
    {
        $duelists = [];
        $adjacent = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        foreach ($adjacent as $locationName)
        {
            $characters = $theah->getCharactersAtLocation($locationName);
            foreach ($characters as $character)
            {
                if ($character->ControllerId == $owner->ControllerId && $character->hasTrait("Duelist"))
                {
                    $duelists[] = $character;
                }
            }
        }
        return $duelists;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventChallengeIssued)) return;
        if ($event->canceled) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;
        if ($event->theah->game->characterIsInDiscardOrLocker($owner)) return;
        if (! $event->theah->cardInCity($owner)) return;

        $challenger = $event->theah->getCharacterById($event->challengerId);
        if ($challenger === null) return;
        if ($challenger->Location != $owner->Location) return;

        if (count($this->getEligibleDuelists($event->theah, $owner)) == 0) return;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if (str_starts_with($reactionId, 'move-'))
        {
            $characterId = (int) substr($reactionId, strlen('move-'));
            $character = $game->theah->getCharacterById($characterId);

            $adjacent = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            if ($character !== null
                && $character->ControllerId == $owner->ControllerId
                && $character->hasTrait("Duelist")
                && in_array($character->Location, $adjacent))
            {
                $moveEvent = EventFactory::createCardMovingEvent(
                    $character->ControllerId,
                    $character->Id,
                    $character->Location,
                    $owner->Location,
                    false,
                    $owner->Id,
                    $this->Id
                );
                $game->theah->queueEvent($moveEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} moves ${character_inject_code} to ${location_name}.'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                    "location_name" => $owner->Location,
                ]);

                $this->setUsed($game->theah, true);
            }
        }

        $game->gamestate->nextState("done");
    }
}
