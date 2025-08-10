<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01039 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move to an Adjacent Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to move to an adjacent location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $philip = $this->getOwningCharacter($theah);
        $adjacentLocations = $theah->getAdjacentCityLocations($philip->Location);
        foreach ($adjacentLocations as $locationName)
        {
            $location = $theah->getCityLocation($locationName);
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Move to %s'), $locationName), "moveTo-$locationName");
        }
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');


        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped)
        {
            $philip = $this->getOwningCharacter($event->theah);
            if ($event->characterId == $philip->Id && $event->theah->cardInCity($philip))
            {
                $reactionEvent = EventFactory::createReactionTransitionEvent($philip->ControllerId, $philip->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
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

            $philip = $this->getOwningCharacter($game->theah);
            $location = $game->theah->getCityLocation($locationName);

            $event = EventFactory::createCardMovedEvent($philip->ControllerId, $philip->Id, $philip->Location, $locationName, $engage=false, $philip->Id);
            $game->theah->queueEvent($event);

            $game->notifyAllPlayers("message", clienttranslate('<strong>Philip Hase</strong>: ${player_name} used Reaction and moved to ${location_name}.'), [
                "i18n" => ["player_name"],
                "player_name" => $game->getPlayerNameById($philip->ControllerId),
                "location_name" => $locationName,
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");

    }
}