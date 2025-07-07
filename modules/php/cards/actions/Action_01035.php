<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01035 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Recruit Mercenary from City Deck");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $kaspar = $this->getOwningCharacter($theah);

        if ($kaspar->Engaged)
        {
            return false;
        }

        if ( ! $theah->cardInCity($kaspar))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $playerId = $event->theah->game->getActivePlayerId();
            
            $kaspar = $this->getOwningCharacter($event->theah);
            $engageEvent = EventFactory::createCardEngagedEvent($playerId, $kaspar->Id);
            $event->theah->eventCheck($engageEvent);
            $event->queueEvent($engageEvent);
            
            $game->notifyAllPlayers("message", clienttranslate('<strong>Kaspar</strong> performed his Recruit Action from the City Deck.'), []);
            $deck = $game->getGameDeckObject();

            $count = $deck->countCardInLocation(Game::LOCATION_CITY_DECK);
            $cards = $game->getCardsOnTopOfCityDeck($count);
            $revealed = [];
            $names = [];
            $found = false;
            $mercenary = null;
            foreach ($cards as $cardInfo)
            {
                $card = $game->getCardObjectFromDb($cardInfo['id']);
                if (in_array("Mercenary", $card->Traits))
                {
                    $revealed[] = $cardInfo['id'];
                    $found = true;
                    $mercenary = $card;
                    $names[] = $card->Name;
                    break;
                }

                $revealed[] = $cardInfo['id'];
                $names[] = $card->Name;
                unset($card);                
            }

            // Per rules team, if no mercenary is found, shuffle the discard pile into the deck and try again.
            if ( ! $found)
            {
                $game->shuffleCityDiscardIntoCityDeck();

                //Stick cards already revealed in the top of the deck
                $revealed = array_reverse($revealed);
                foreach ($revealed as $cardId)
                {
                    $deck->insertCardOnExtremePosition($cardId, Game::LOCATION_CITY_DECK, true);
                }

                $revealed = [];
                $names = [];
                $count = $deck->countCardInLocation(Game::LOCATION_CITY_DECK);
                $cards = $game->getCardsOnTopOfCityDeck($count);    
                //$cards = $deck->getCardsInLocation(Game::LOCATION_CITY_DECK, null, 'card_location_arg');
                foreach ($cards as $cardInfo)
                {
                    $card = $game->getCardObjectFromDb($cardInfo['id']);
                    if (in_array("Mercenary", $card->Traits))
                    {
                        $revealed[] = $cardInfo['id'];
                        $names[] = $card->Name;
                        $found = true;
                        $mercenary = $card;
                        break;
                    }

                    $revealed[] = $cardInfo['id'];
                    $names[] = $card->Name;
                    unset($card);
                }
            }

            $names = implode(", ", $names);
            $game->notifyAllPlayers("message", clienttranslate('A total of ${count} City Cards were revealed: ${names}'), [
                'count' => count($revealed),
                'names' => $names,
            ]);

            if ($found)
            {
                $game->globals->set(Game::CHOSEN_CARD, $mercenary->Id);
                $game->notifyAllPlayers("message", clienttranslate('${mercenary} is the first Mercenary revealed in the City Deck.'), [
                    'mercenary' => $mercenary->Name,
                ]);

                $addEvent = EventFactory::createCityCardAddedToLocationEvent($mercenary->Id, $kaspar->Location);
                $event->queueEvent($addEvent);
    
            }
            else
            {
                $game->globals->delete(Game::CHOSEN_CARD);
                $game->notifyAllPlayers("message", clienttranslate('No mercenary was found in the City Deck.'), []);
            }

            $game->globals->set(Game::REVEALED_CARDS, json_encode($revealed));

            $revealEvent = EventFactory::createTransitionEvent($playerId, $kaspar->Id, "01035", $this->Id);
            $event->queueEvent($revealEvent);

            $this->setUsed($event->theah, true);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01035)
        {
            $revealed = json_decode($game->globals->get(Game::REVEALED_CARDS), true);
            $cards = [];
            foreach ($revealed as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                $cards[] = $card->getPropertyArray($game);
                unset($card);
            }
            $args['cards'] = $cards;

            $args['kasparId'] = $this->getOwningCharacter($game->theah)->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01035_3 || 
            $state == States::HIGH_DRAMA_PLAYER_TURN_01035_4)
        {
            $args['kasparId'] = $this->getOwningCharacter($game->theah)->Id;

            $mercenaryId = $game->globals->get(Game::CHOSEN_CARD);
            $mercenary = $game->getCardObjectFromDb($mercenaryId);
            $args['character'] = $mercenary->getPropertyArray($game);
        }

        return $args;
    }

    public function stateFromAction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01035_2)
        {
            //Sink all the cards except the revealed mercenary
            $mercenaryId = $game->globals->get(Game::CHOSEN_CARD);
            $revealed = json_decode($game->globals->get(Game::REVEALED_CARDS), true);
            $cards = [];
            foreach ($revealed as $cardId)
            {
                if ($cardId == $mercenaryId)
                {
                    continue;
                }

                $card = $game->getCardObjectFromDb($cardId);
                $cards[] = $card->getPropertyArray($game);
                $event = EventFactory::createCardAddedToCityDiscardPileEvent($card->ControllerId, $card->Id, Game::LOCATION_CITY_DECK);
                $game->theah->queueEvent($event);
            }

            if ($mercenaryId)
            {
                $currentPlayerId = $game->globals->get(Game::CURRENT_PLAYER);
                $game->gamestate->changeActivePlayer($currentPlayerId);   
                $game->gamestate->nextState("found");
            }
            else
            {
                $game->gamestate->nextState("notFound");
            }
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01035_3)
        {
            $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses not to recruit the revealed mercenary.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);

            $mercenaryId = $game->globals->get(Game::CHOSEN_CARD);
            $kaspar = $this->getOwningCharacter($game->theah);
            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($kaspar->ControllerId, $mercenaryId, $kaspar->Location);
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState("pass");
        }

    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01035_3)
        {
            $mercenaryId = $game->globals->get(Game::CHOSEN_CARD);
            $mercenary = $game->getCardObjectFromDb($mercenaryId);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses to recruit ${mercenary}.'), [
                "player_name" => $game->getActivePlayerName(),
                "mercenary" => $mercenary->Name,
            ]);

            $game->globals->set(Game::RECRUIT_TYPE, Game::KASPAR_RECRUIT_TYPE);
            $game->gamestate->nextState("recruit");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01035_4)
        {
            $mercenaryId = $game->globals->get(Game::CHOSEN_CARD);
            $mercenary = $game->getCardObjectFromDb($mercenaryId);
            $kaspar = $this->getOwningCharacter($game->theah);

            if ($id == 1)
            {
                $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses to parley with ${mercenary}.'), [
                    "player_name" => $game->getActivePlayerName(),
                    "mercenary" => $mercenary->Name,
                ]);

                //Set the discount for recruiting a mercenary.
                $discount = $game->theah->getParleyDiscount($kaspar, true);
                $game->globals->set(Game::DISCOUNT, $discount);
            }
            else
            {
                $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses not to parley with ${mercenary}.'), [
                    "player_name" => $game->getActivePlayerName(),
                    "mercenary" => $mercenary->Name,
                ]);
            
                $game->globals->set(Game::DISCOUNT, 0);
            }

            $game->globals->set(Game::CHOSEN_PERFORMER, $kaspar->Id);

            $game->gamestate->nextState("parleyChosen");
        }
    }
}