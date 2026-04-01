<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01173 extends RiskReaction
{
    private int $CharacterId = 0;
    private string $ToLocation = "";

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("After Moving Character, Move Character Again");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $character = $theah->getCharacterById($this->CharacterId);
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('${you} may move a %s again to an adjacent City Location: '), $theah->game->translate($character->Name));
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $character = $theah->getCharacterById($this->CharacterId);
        $adjacentLocations = $theah->getAdjacentCityLocations($this->ToLocation, false);
        foreach ($adjacentLocations as $location)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Move %s to %s'), $theah->game->translate($character->Name), $theah->game->translate($location)), "moveAgain-$location");
        }
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $this->isAvailable() && $event->theah->locationInCity($event->toLocation))
        {
            $owner = $this->getOwningCard($event->theah);
            $card = $event->theah->getCardById($event->cardId);
            if ($owner->Location == Game::LOCATION_HAND && $card instanceof Character && $card->ControllerId == $owner->ControllerId)
            {
                $this->ToLocation = $event->toLocation;
                $this->CharacterId = $event->cardId;
                $owner->IsUpdated = true;

                $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transitionEvent);
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $location = str_replace("moveAgain-", "", $event->reactionId);
            $adjacentLocations = $event->theah->getAdjacentCityLocations($this->ToLocation, false);
            if ($event->theah->locationInCity($location) && in_array($location, $adjacentLocations))
            {
                $game = $event->theah->game;
                $owner = $this->getOwningCard($game->theah);
                $character = $game->theah->getCharacterById($this->CharacterId);
                $event = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, $location, false, $owner->Id, $this->Id);
                $game->theah->queueEvent($event);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and moved ${character_inject_code} to ${location_name}.'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                    "location_name" => $game->translate($location),
                ]);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'decline')
        {
            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}