<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02014 extends CardAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure Location.  Manipulate Top Four Cards of City Deck.");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        $leaders = $theah->getCharactersInCityByPlayerId($owner->ControllerId);
        $leaders = array_filter($leaders, fn($leader) => $leader->hasTrait("Leader"));

        return count($leaders) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        $leaders = $theah->getCharactersInCityByPlayerId($owner->ControllerId);
        $leaders = array_filter($leaders, fn($leader) => $leader->hasTrait("Leader"));
        return array_values($leaders);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);

            $game = $event->theah->game;
            $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::KASPARS_OCCUPATION_PRESSURE_TYPE);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);   
            
            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_COMBAT);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $performer->Id, $performer->Location, $pressureStats);
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);

            $this->announceAction($game);
            $this->setUsed($event->theah, true);
            $this->resetPlayerPassCount($game);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            if ($event->success)
            {
                $owner = $this->getOwningCard($event->theah);

                $game = $event->theah->game;
                $deckCards = $game->getCardsOnTopOfCityDeck(4);
    
                $cards = [];
                foreach ($deckCards as $deckCard)
                {
                    $card = $game->getCardObjectFromDb($deckCard['id']);
                    $cards[] = $card->getPropertyArray($game);
                }
    
                $game->globals->set(Game::CHOSEN_CARD, json_encode($cards));

                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02014", $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02014 || $state == States::HIGH_DRAMA_PLAYER_TURN_02014_2)
        {
            $cards = json_decode($game->globals->get(Game::CHOSEN_CARD));
            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02014)
        {
            $owner = $this->getOwningCard($game->theah);
            $deck = $game->getGameDeckObject();

            foreach ($ids as $id)
            {
                $card = $game->getCardObjectFromDb($id);
                if ($card == null)
                {
                    throw new UserException($game->translate("Invalid card id"));
                }

                if ($card->Location != Game::LOCATION_CITY_DECK)
                {
                    throw new UserException($game->translate("Card %s is not in the City Deck"), $card->Name);
                }
            }

            $originalCards = json_decode($game->globals->get(Game::CHOSEN_CARD));

            foreach ($ids as $id)
            {
                $notification = "cardAddedToCityDiscardPile";

                $card = $game->getCardObjectFromDb($id);
                $game->notify->all($notification, clienttranslate('${card_inject_code} has been moved to the City Discard pile.'), [
                    "card_inject_code" => $card->getInjectCode(),
                    "card" => $card->getPropertyArray($game),
                ]);

                $deck->moveCard($id, Game::LOCATION_CITY_DISCARD);
                //Remove the card from the original cards
                $originalCards = array_filter($originalCards, fn($originalCard) => $originalCard->id != $id);
            }

            if (count($originalCards) == 0)
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState("allDiscarded");
            }
            else
            {
                $game->globals->set(Game::CHOSEN_CARD, json_encode(array_values($originalCards)));

                $game->gamestate->nextState("cardsChosen");
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02014_2)
        {
            $deck = $game->getGameDeckObject();

            $remainingCards = json_decode($game->globals->get(Game::CHOSEN_CARD));

            $remainingIds = array_map(fn($remainingCard) => $remainingCard->id, $remainingCards);

            foreach ($ids as $id) 
            {
                if (!in_array($id, $remainingIds))
                {
                    throw new UserException(sprintf($game->translate("Card %s is not in the remaining cards."), $id));
                }

                //Move card to top of deck
                $deck->insertCardOnExtremePosition((int)$id, Game::LOCATION_CITY_DECK, true);                
            }

            $owner = $this->getOwningCard($game->theah);
            $message = clienttranslate('${card_inject_code}: ${player_name} has chosen the order of the remaining cards in the City Deck.');
            $game->notify->all("message", $message, [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}