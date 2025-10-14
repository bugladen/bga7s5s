<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

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

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
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
            $playerId = $event->playerId;
            
            $kaspar = $this->getOwningCharacter($event->theah);
            $engageEvent = EventFactory::createCardEngagedEvent($playerId, $kaspar->Id);
            $event->theah->eventCheck($engageEvent);
            $event->queueEvent($engageEvent);

            $this->announceAction($game);

            $mercenary = $game->revealFirstCardTypeFromCityDeck($playerId, "Mercenary", $kaspar->Id);

            if ($mercenary)
            {
                $game->globals->set(Game::CHOSEN_CARD, $mercenary->Id);
                $game->notify->all("message", clienttranslate('${mercenary_inject_code} is the first Mercenary revealed in the City Deck.'), [
                    "mercenary_inject_code" => $mercenary->getInjectCode(),
                ]);

                $addEvent = EventFactory::createCityCardAddedToLocationEvent($mercenary->Id, $kaspar->Location);
                $event->queueEvent($addEvent);
    
            }
            else
            {
                $game->globals->delete(Game::CHOSEN_CARD);
                $game->notify->all("message", clienttranslate('No mercenary was found in the City Deck.'), []);
            }

            $game->notify->all("message", clienttranslate('The rest of the revealed cards have been sunk.'), []);

            $revealEvent = EventFactory::createTransitionEvent($playerId, $kaspar->Id, "01035", $this->Id);
            $event->queueEvent($revealEvent);

            $this->resetPlayerPassCount($game);
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
            $mercenaryId = $game->globals->get(Game::CHOSEN_CARD);
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
            $game->notify->all("message", clienttranslate('${player_name} chooses not to recruit the revealed mercenary.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);

            $mercenaryId = $game->globals->get(Game::CHOSEN_CARD);
            $kaspar = $this->getOwningCharacter($game->theah);
            $sinkEvent = EventFactory::createCardAddedToCityDeckEvent($kaspar->ControllerId, $mercenaryId, false);
            $game->theah->queueEvent($sinkEvent);

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

            $game->notify->all("message", clienttranslate('${player_name} chooses to recruit ${mercenary}.'), [
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
                $game->notify->all("message", clienttranslate('${player_name} chooses to parley with ${mercenary}.'), [
                    "player_name" => $game->getActivePlayerName(),
                    "mercenary" => $mercenary->Name,
                ]);

                //Set the discount for recruiting a mercenary.
                [$discount, $explanations] = $game->theah->getParleyDiscount($kaspar, true);
                if ($discount != 0)
                $game->notify->player($kaspar->ControllerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
                    "explanations" => $explanations,
                ]);
        
                $game->globals->set(Game::DISCOUNT, $discount);
                $game->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${player_name} chooses not to parley with ${mercenary}.'), [
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