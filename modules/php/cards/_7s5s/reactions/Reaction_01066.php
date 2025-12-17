<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01066 extends CardReaction
{
    private int $FollowedCharacterId = 0;
    private string $FollowedCharacterLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Follow Opposing Character to Adjacent Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may follow opposing Character to adjacent Location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Follow Character'), 'followCharacter');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $character = $event->theah->getCardById($event->cardId);
            if ($event->theah->cardInCity($owner) && 
                $character instanceof Character &&             
                $character->ControllerId != $owner->ControllerId && 
                $event->fromLocation == $owner->Location)
            {
                $adjacentLocations = $event->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
                if (in_array($event->toLocation, $adjacentLocations))
                {
                    $this->FollowedCharacterId = $character->Id;
                    $this->FollowedCharacterLocation = $event->toLocation;
                    $owner->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'followCharacter')
        {
            $owner = $this->getOwningCharacter($game->theah);
            $character = $game->theah->getCardById($this->FollowedCharacterId);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and followed ${character_inject_code} to ${location_name}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
                "location_name" => $game->translate($this->FollowedCharacterLocation),
            ]);

            $event = EventFactory::createCardMovedEvent($owner->ControllerId, $owner->Id, $owner->Location, $this->FollowedCharacterLocation, $engage = false, $owner->Id);
            $game->theah->queueEvent($event);

            $this->setUsed($game->theah, true);
        }
        
        $game->gamestate->nextState("done");
    }
}