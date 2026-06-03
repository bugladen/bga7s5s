<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01099b extends CardReaction
{
    private string $claimedLocation;
    
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Add a Renown to a Different Location after a Location is Claimed");
        $this->claimedLocation = "";
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may add a Renown to a different location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $locations = $theah->getCityLocations();
        foreach ($locations as $location)
        {
            if ($location->Name == $this->claimedLocation)
            {
                continue;
            }

            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate($location->Name), "addReknown-$location->Name");
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
            if ($owner->ControllerId != $event->playerId)
            {
                $this->claimedLocation = $event->location;
                $owner->IsUpdated = true;

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
            $locationName = str_replace("addReknown-", "", $reactionId);
            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createRenownAddedToLocationEvent($owner->ControllerId, $locationName, 1, $owner->getInjectCode());
            $game->theah->queueEvent($event);

            $game->notify->all("message", clienttranslate('${player_name} uses ${reaction_inject_code} to add a Renown to ${location_name}.'), [
                'player_name' => $game->getActivePlayerName(),
                'reaction_inject_code' => $owner->getInjectCode(),
                'location_name' => $locationName,
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
