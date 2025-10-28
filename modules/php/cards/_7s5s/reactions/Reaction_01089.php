<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01089 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Soline to Adjecent Location after Action Resolves");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to move Soline to an adjacent location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        
        $owner = $this->getOwningCharacter($theah);
        $adjacentLocations = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        foreach ($adjacentLocations as $locationName)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Move Soline to %s'), $locationName), "moveTo-$locationName");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), "pass");

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionResolved && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($event->theah->cardInCity($owner))
            {
                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            //Get the location name from the reactionId
            $locationName = str_replace("moveTo-", "", $reactionId);

            $soline = $this->getOwningCharacter($game->theah);

            $event = EventFactory::createCardMovedEvent($soline->ControllerId, $soline->Id, $soline->Location, $locationName, $engage=false, $soline->Id);
            $game->theah->queueEvent($event);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and moved to ${location_name}.'), [
                "reaction_inject_code" => $soline->getInjectCode(),
                "player_name" => $game->getPlayerNameById($soline->ControllerId),
                "location_name" => $locationName,
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}