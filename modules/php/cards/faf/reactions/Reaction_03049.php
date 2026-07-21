<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03049 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Ekaterina after her location is claimed");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to move Ekaterina to a different City location: ');
    }

    /**
     * @return string[] city location names Ekaterina can move to (excludes her current location)
     */
    private function getEligibleDestinations(Theah $theah, Character $owner): array
    {
        $destinations = [];
        foreach (array_keys($theah->getCityLocations()) as $locationName)
        {
            if ($locationName != $owner->Location)
            {
                $destinations[] = $locationName;
            }
        }
        return $destinations;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            foreach ($this->getEligibleDestinations($theah, $owner) as $locationName)
            {
                $array[] = $this->createButtonProperty(
                    $theah->game,
                    sprintf($theah->game->translate('Move Ekaterina to %s'), $theah->game->translate($locationName)),
                    "moveTo-$locationName"
                );
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Unlabelled "After … may …" like Angeline (_03025) — Continuous Reaction.
        // Competitive notes confirm any claim at her location (either player) triggers.
        if (! ($event instanceof EventLocationClaimed)) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;
        if ($owner->ControllerId == 0) return;
        if ($event->theah->game->characterIsInDiscardOrLocker($owner)) return;
        if (! $event->theah->cardInCity($owner)) return;
        if ($event->location != $owner->Location) return;

        if (count($this->getEligibleDestinations($event->theah, $owner)) == 0) return;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass' && str_starts_with($reactionId, 'moveTo-'))
        {
            $locationName = substr($reactionId, strlen('moveTo-'));
            $ekaterina = $this->getOwningCharacter($game->theah);

            $valid = $this->getEligibleDestinations($game->theah, $ekaterina);
            if (in_array($locationName, $valid, true))
            {
                $moveEvent = EventFactory::createCardMovingEvent(
                    $ekaterina->ControllerId,
                    $ekaterina->Id,
                    $ekaterina->Location,
                    $locationName,
                    false,
                    $ekaterina->Id,
                    $this->Id
                );
                $game->theah->queueEvent($moveEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} moved to ${location_name} after the location was claimed.'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $ekaterina->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($ekaterina->ControllerId),
                    "location_name" => $locationName,
                ]);

                // Continuous Reaction: intentionally do NOT call $this->setUsed(true).
                // The reaction remains available and can fire on every claim at her location.
            }
        }

        $game->gamestate->nextState("done");
    }
}
