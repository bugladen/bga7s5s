<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Wardogb extends AttachmentReaction
{
    private string $pressureLocation = '';

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate.
        $this->Name = "Add +1 to your pressure total at adjacent City location";
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        $location = $this->pressureLocation !== '' ? $this->pressureLocation : 'an adjacent City location';
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return $base . sprintf('${you} may add +1 to your total for the pressure at %s: ', $location);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, 'Add +1', 'use');
        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventPressureOccuring) || ! $this->isAvailable())
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        $owningCharacter = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! ($owningCharacter instanceof Character))
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owningCharacter))
        {
            return;
        }

        // WHY: Covert Reaction — only at uncontrolled City locations (Seadog / Valeri / Robin).
        if (! $event->theah->cardInCity($owningCharacter))
        {
            return;
        }

        $cityLocation = $event->theah->getCityLocation($owningCharacter->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            return;
        }

        if (! $event->theah->locationInCity($event->location))
        {
            return;
        }

        $adjacent = $event->theah->getAdjacentCityLocations($owningCharacter->Location, false);
        if (! in_array($event->location, $adjacent, true))
        {
            return;
        }

        $this->pressureLocation = $event->location;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningAttachment($game->theah);

        if ($reactionId === 'use' && $this->pressureLocation !== '')
        {
            // WHY: Loyal / Meeting of the Minds — flag read later in pressureLocation() after reactions resolve.
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::WARDOG_PRESSURE_TYPE);
            $game->globals->set(Game::WARDOG_PLAYER_ID, $owner->ControllerId);

            $game->notify->all("message", '${reaction_inject_code}: ${player_name} adds +1 to their total for the pressure at ${location}.', [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "location" => $this->pressureLocation,
            ]);

            $this->setUsed($game->theah, true);
        }

        $this->pressureLocation = '';
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
