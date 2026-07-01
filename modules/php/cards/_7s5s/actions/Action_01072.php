<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01072 extends CardAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure Location with Non-Mercenaries");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        if ($this->RequiresPerformerSelected)
        {
            $owner = $this->getOwningCard($theah);
            $leaders = $theah->getCharactersInCityByPlayerId($owner->ControllerId);
            $leaders = array_filter($leaders, fn($leader) => $leader->hasTrait("Leader") && $leader->canPressure(Game::STAT_INFLUENCE));
            return count($leaders) > 0;
        }
        else
        {
            //Regression: Remove once all games have started after release date
            $leader = $theah->getLeaderByPlayerId($playerId);
            return ! $leader->Engaged && $theah->cardInCity($leader);
        }
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        $leaders = $theah->getCharactersInCityByPlayerId($owner->ControllerId);
        $leaders = array_filter($leaders, fn($leader) => $leader->hasTrait("Leader") && $leader->canPressure(Game::STAT_INFLUENCE));
        return array_values($leaders);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            if ($this->RequiresPerformerSelected)
            {
                $leaderId = $game->globals->get(Game::CHOSEN_PERFORMER);
                $leader = $event->theah->getCharacterById($leaderId);
            }
            else
            {
                //Regression: Remove once all games have started after release date
                $leader = $event->theah->getLeaderByPlayerId($event->playerId);
            }

            $scheme = $this->getOwningCard($event->theah);

            $engageEvent = EventFactory::createCardEngagedEvent($event->playerId, $leader->Id, $scheme->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);

            $game->globals->set(Game::CHOSEN_PERFORMER, $leader->Id);
            $game->globals->set(Game::PRESSURING_PLAYER, $leader->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::REPUTATION_MERITEE_PRESSURE_TYPE);

            $pressureStats = $event->theah->getPressureStats($leader, $leader->Location, Game::STAT_INFLUENCE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($leader->ControllerId, $leader->Id, $leader->Location, $pressureStats);
            $event->theah->queueEvent($pressureOccuringEvent);

            $scheme = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $scheme->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {            
            $owner = $this->getOwningCard($event->theah);

            if ($event->success)
            {
                $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01072", $this->Id);
                $event->theah->queueEvent($transitionEvent);
            }
            else
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $event->theah->queueEvent($actionResolvedEvent);
            }
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01072)
        {
            //Get a list of City Cards at the leader's location
            $leader = $game->theah->getLeaderByPlayerId($game->getActivePlayerId());
            $cards = $game->theah->getCardObjectsAtLocation($leader->Location);
            $cards = array_values(array_filter($cards, fn($card) => ! $card->isControlled() && $card instanceof ICityDeckCard));
            $ids = array_map(fn($card) => $card->Id, $cards);

            $args["targetCardIds"] = $ids;

            $scheme = $this->getOwningCard($game->theah);
            $args["schemeId"] = $scheme->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01072_2)
        {
            $args["chosenCardId"] = $game->globals->get(Game::CHOSEN_CARD);

            $scheme = $this->getOwningCard($game->theah);
            $args["schemeId"] = $scheme->Id;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01072)
        {
            $leader = $game->theah->getLeaderByPlayerId($game->getActivePlayerId());

            //If 0 was passed in then no available city cards are assumed to be at that location
            if ($id == 0)
            {
                $cards = $game->theah->getCardObjectsAtLocation($leader->Location);
                $cards = array_values(array_filter($cards, fn($card) => ! $card->isControlled() && $card instanceof ICityDeckCard));
                if (count($cards) > 0)
                {
                    throw new UserException($game->translate("There are available City Cards at the Leader's location"));
                }

                $game->globals->set(Game::CHOSEN_CARD, 0);
                $game->gamestate->nextState();
                return;
            }

            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new UserException($game->translate("Invalid card id"));
            }

            if ( ! $card instanceof ICityDeckCard)
            {
                throw new UserException($game->translate("Card is not a City Card"));
            }

            if ($leader->Location != $card->Location)
            {
                throw new UserException($game->translate("Card is not at the Leader's location"));
            }

            $game->globals->set(Game::CHOSEN_CARD, $card->Id);
            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01072_2)
        {
            $playerId = $game->getActivePlayerId();
            $cards = $game->theah->getCardObjectsAtLocation(Game::LOCATION_APPROACH, $playerId);
            $musterCard = null;

            //Make sure there actually are no available cards to muster
            if ($id == 0)
            {
                $cards = array_values(array_filter($cards, fn($card) => $card instanceof Character));
                if (count($cards) > 0)
                {
                    throw new UserException($game->translate("There are available Character cards in your Approach Deck"));
                }
            }
            else
            {
                $cards = array_values(array_filter($cards, fn($card) => $card->Id == $id && $card instanceof Character));
                if (count($cards) == 0)
                {
                    throw new UserException($game->translate("Invalid card id"));
                }

                $musterCard = $cards[0];
            }

            $discardedCard = $game->globals->get(Game::CHOSEN_CARD);
            if ($discardedCard != 0)
            {
                $game->notify->all("message", clienttranslate('${player_name} chose to discard ${discarded_card}'), [
                    "player_name" => $game->getPlayerNameById($game->getActivePlayerId()),
                    "discarded_card" => $game->theah->getCardById($discardedCard)->Name,
                ]);

                $discardedCard = $game->theah->getCardById($discardedCard);
                $event = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $discardedCard->Id, $discardedCard->Location);
                $game->theah->queueEvent($event);
            }

            if ($musterCard != null)
            {                
                $game->notify->all("message", clienttranslate('${player_name} chose to muster ${muster_card}'), [
                    "player_name" => $game->getPlayerNameById($playerId),
                    "muster_card" => $musterCard->Name,
                ]);

                $leader = $game->theah->getLeaderByPlayerId($playerId);
                $event = EventFactory::createCharacterMusteredEvent($playerId, $musterCard->Id, $leader->Location);
                $game->theah->queueEvent($event);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cardChosen");
        }
    }
}