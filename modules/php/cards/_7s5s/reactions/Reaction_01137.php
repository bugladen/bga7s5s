<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01137 extends RiskReaction
{
    private string $FromLocation;
    private string $ToLocation;
    private int $TargetCharacterId;
    private int $FollowCharacterId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Follow Enemy Character and Wound Them");
        $this->ToLocation = "";
        $this->TargetCharacterId = 0;
    }
    
    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may follow enemy Character to new Location and Wound Them: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCard($theah);
        if ($this->FromLocation != "")
        {
            $charactersAtLocation = $theah->getCharactersAtLocationByPlayerId($this->FromLocation, $owner->ControllerId);
            foreach ($charactersAtLocation as $character)
            {
                $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Follow with %s'), $character->Name), "follow-{$character->Id}");
            }     
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $card = $event->theah->getCardById($event->cardId);

                $charactersAtLocation = $event->theah->getCharactersAtLocationByPlayerId($event->fromLocation, $owner->ControllerId);
                if ($card->ControllerId != $owner->ControllerId 
                && $event->fromLocation != Game::LOCATION_PLAYER_HOME 
                && $event->toLocation != Game::LOCATION_PLAYER_HOME 
                && count($charactersAtLocation) > 0)
                {
                    $this->FromLocation = $event->fromLocation;
                    $this->ToLocation = $event->toLocation;
                    $this->TargetCharacterId = $event->cardId;
                    $owner->IsUpdated = true;
    
                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($game->theah);

            $character = $game->theah->getCardById($this->FollowCharacterId);
            $target = $game->theah->getCardById($this->TargetCharacterId);

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $this->FollowCharacterId, $character->Location, $this->ToLocation, false, $owner->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $woundEvent = EventFactory::createCharacterWoundedEvent($this->TargetCharacterId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${character_name} follows ${target_name} to ${location} and wounds them'), [
                'card_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_name' => $character->getInjectCode(),
                'location' => $this->ToLocation,
                'target_name' => $target->getInjectCode(),
            ]);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);
            
            $this->FollowCharacterId = str_replace("follow-", "", $reactionId);
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}

