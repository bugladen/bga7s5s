<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05GaryFlipped extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "En Garde Rena after Opposing Character Destroyed";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may En Garde Rena: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, "En Garde", 'enGarde');
        $array[] = $this->createButtonProperty($theah->game, "Pass", 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCharacterDestroyed) || ! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null)
        {
            return;
        }

        // WHY: En Garde only matters when Rena is Engaged; skip useless Pass-only prompt.
        if (! $owner->Engaged)
        {
            return;
        }

        $destroyed = $event->theah->getCharacterById($event->characterId);
        if (! ($destroyed instanceof Character))
        {
            return;
        }

        // WHY: Printed "opposing" — different controller. Location not required by text.
        // EventCharacterDestroyed has runEventHubAfterCards; Location may already be locker
        // — do not gate on destroyed.Location == owner.Location.
        if ($destroyed->ControllerId == 0 || $destroyed->ControllerId == $owner->ControllerId)
        {
            return;
        }

        if ($event->characterId == $owner->Id)
        {
            return;
        }

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'enGarde')
        {
            $owner = $this->getOwningCharacter($game->theah);
            if ($owner !== null && $owner->Engaged)
            {
                $event = EventFactory::createCardEngardedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($event);

                $game->notify->all("message", '${reaction_inject_code}: ${player_name} used Reaction to En Garde ${rena_inject_code}.', [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "rena_inject_code" => $owner->getInjectCode(),
                ]);
            }

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
