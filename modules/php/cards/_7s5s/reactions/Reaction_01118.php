<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01118 extends CardReaction
{

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Renown to Elina's Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to move Renown to Elina\'s Location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $elina = $this->getOwningCharacter($theah);

        // WHY: Renown only exists on city locations. Player Home shares one string
        // across players and is not in cityLocations — moving renown there crashes EventHub.
        if (!$theah->cardInCity($elina))
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
            return $array;
        }

        $locations = $theah->getCityLocations();
        foreach ($locations as $location)
        {
            if ($location->Name == $elina->Location)
            {
                continue;
            }

            if ($location->Renown > 0)
            {
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate("From ") . $theah->game->translate($location->Name), "moveRenown-$location->Name");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventSorcererAbilityPlayed && $this->isAvailable())
        {
            $elina = $this->getOwningCharacter($event->theah);

            if (($event->sourceId == $elina->Id || $event->performerId == $elina->Id) && $event->theah->cardInCity($elina))
            {
                $transition = EventFactory::createReactionTransitionEvent($elina->ControllerId, $elina->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            $elina = $this->getOwningCharacter($game->theah);

            // WHY: Elina may have moved Home after the reaction was queued but before
            // the player chose a source location — skip the move rather than crash EventHub.
            if (!$game->theah->cardInCity($elina))
            {
                $game->gamestate->nextState("done");
                return;
            }

            $location = str_replace("moveRenown-", "", $reactionId);

            $batchId = $game->getNextEventBatchId();
            $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent($elina->ControllerId, $location, $elina->Location, 1, $elina->getInjectCode());
            $movingEvent->batchId = $batchId;
            $game->theah->eventCheck($movingEvent);
            $game->theah->queueEvent($movingEvent);

            $reknownRemovedEvent = EventFactory::createRenownRemovedFromLocationEvent($elina->ControllerId, $location, 1, $elina->getInjectCode());
            $reknownRemovedEvent->batchId = $batchId;
            $game->theah->eventCheck($reknownRemovedEvent);
            $game->theah->queueEvent($reknownRemovedEvent);

            $reknownAddedEvent = EventFactory::createRenownAddedToLocationEvent($elina->ControllerId, $elina->Location, 1, $elina->getInjectCode(), $isMove = true);
            $reknownAddedEvent->batchId = $batchId;
            $game->theah->eventCheck($reknownAddedEvent);
            $game->theah->queueEvent($reknownAddedEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction to move Renown from ${location_name} to her Location.'), [
                "player_name" => $game->getPlayerNameById($elina->ControllerId),
                "reaction_inject_code" => $elina->getInjectCode(),
                "location_name" => $location,
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }

}