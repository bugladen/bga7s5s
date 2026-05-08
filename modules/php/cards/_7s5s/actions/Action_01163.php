<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01163_CardClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_01163 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Manipulate Top 3 Cards of Your Deck");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01163", $this->Id);
            $event->theah->queueEvent($transitionEvent);

            //Get the top 3 cards of the player's deck
            $game = $event->theah->game;
            $deck = $game->getGameDeckObject();
            $location = $game->getPlayerFactionDeckName($owner->ControllerId);
            $cards = $deck->getCardsOnTop(3, $location);
            $cardIds = array_map(function($card) { return $card['id']; }, $cards);
            $game->globals->set(Game::REVEALED_CARDS, $cardIds);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01163 || $state == States::HIGH_DRAMA_PLAYER_TURN_01163_2 || $state == States::HIGH_DRAMA_PLAYER_TURN_01163_3)
        {
            $cardIds = $game->globals->get(Game::REVEALED_CARDS);
            $cards = [];
            foreach ($cardIds as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                $cards[] = $card->getPropertyArray($game);
            }
            $args['cards'] = $cards;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01163_3)
        {
            $locations = $game->theah->getCityLocations();
            $ids = array_map(fn($location) => $location->Name, array_values($locations));
            $args['locationIds'] = $ids;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01163 || $state == States::HIGH_DRAMA_PLAYER_TURN_01163_2)
        {
            $card = $game->getCardObjectFromDb($id);
            if ($card == null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            $owner = $this->getOwningCard($game->theah);
            $location = $game->getPlayerFactionDeckName($owner->ControllerId);
            if ($card->Location != $location)
            {
                throw new UserException($game->translate("Card is not in your faction deck"));
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01163)
        {
            $card = $game->getCardObjectFromDb($id);
            $deck = $game->getGameDeckObject();
            $deck->insertCardOnExtremePosition($card->Id, $location, false);

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${player_name} has chosen a card to sink to the bottom of their faction deck.'), [
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
            ]);

        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01163_2)
        {
            $card = $game->getCardObjectFromDb($id);
            $owner = $this->getOwningCard($game->theah);
            $addEvent = EventFactory::createCardAddedToHandEvent($owner->ControllerId, $card->Id, true);
            $game->theah->queueEvent($addEvent);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01163 || $state == States::HIGH_DRAMA_PLAYER_TURN_01163_2)
        {
            $cardIds = $game->globals->get(Game::REVEALED_CARDS);
            $cardIds = array_filter($cardIds, fn($cardId) => $cardId != $id);
            $game->globals->set(Game::REVEALED_CARDS, $cardIds);

            $game->gamestate->nextState();
        }

    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01163_3)
        {
            $location = $ids[0];

            $locations = $game->theah->getCityLocations();
            $locationNames = array_map(fn($location) => $location->Name, array_values($locations));
            if (!in_array($location, $locationNames))
            {
                throw new UserException($game->translate("Location is not a valid city location"));
            }

            $cardIds = $game->globals->get(Game::REVEALED_CARDS);
            $card = $game->getCardObjectFromDb(array_shift($cardIds));

            //Move the saved card to the hidden location
            $deck = $game->getGameDeckObject();
            $deck->moveCard($card->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            //Create a clone of the saved card
            $owner = $this->getOwningCard($game->theah);
            $cloneCard = $game->createCardInLocation('01163_CardClone', $location, $owner->ControllerId, $owner->ControllerId);
            $game->theah->addCardToWorld($cloneCard);
            $cloneCard->Name = $card->Name;
            $cloneCard->Image = $card->Image;
            $cloneCard->CardBackImage = $card->CardBackImage;
            $cloneCard->FaceDown = true;
            if ($cloneCard instanceof _01163_CardClone)
            {
                $cloneCard->ClonedCardId = $card->Id;
                $cloneCard->ParentCardId = $owner->Id;
            }
            $game->updateCardObjectInDb($cloneCard);
            $game->theah->addCardToWorld($cloneCard);

            $playEvent = EventFactory::createCardMusteredEvent($owner->ControllerId, $cloneCard->Id, $location);
            $game->theah->queueEvent($playEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
       }
    }
}