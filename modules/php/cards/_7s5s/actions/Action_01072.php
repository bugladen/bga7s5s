<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

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
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $leader = $theah->getLeaderByPlayerId($playerId);
        return ! $leader->Engaged && $theah->cardInCity($leader);
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        $leader = $theah->getLeaderByPlayerId($playerId);
        $performers += [$leader];

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $leader = $event->theah->getLeaderByPlayerId($event->playerId);

            $scheme = $this->getOwningCard($event->theah);
            $game->notify->all("message", clienttranslate('${action_inject_code}: ${player_name} used Action.
            They will pressure the location, discard a City Card, and muster a Character from their Approach Deck.'), [
                "action_inject_code" => $scheme->getInjectCode(),
                "player_name" => $game->getPlayerNameById($leader->ControllerId)
            ]);

            $engageEvent = EventFactory::createCardEngagedEvent($event->playerId, $leader->Id);
            $event->theah->queueEvent($engageEvent);

            $game->globals->set(Game::CHOSEN_PERFORMER, $leader->Id);
            $game->globals->set(Game::PRESSURING_PLAYER, $leader->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::REPUTATION_MERITEE_PRESSURE_TYPE);

            $pressureStats = $event->theah->getPressureStats($leader, Game::STAT_INFLUENCE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($leader->ControllerId, $leader->Id, $leader->Location, $pressureStats);
            $event->theah->queueEvent($pressureOccuringEvent);

            $scheme = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $scheme->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id && $event->success)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01072", $this->Id);
            $event->theah->queueEvent($transitionEvent);
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
                    throw new \BgaUserException($game->translate("There are available City Cards at the Leader's location"));
                }

                $game->globals->set(Game::CHOSEN_CARD, 0);
                $game->gamestate->nextState();
                return;
            }

            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Invalid card id"));
            }

            if ( ! $card instanceof ICityDeckCard)
            {
                throw new \BgaUserException($game->translate("Card is not a City Card"));
            }

            if ($leader->Location != $card->Location)
            {
                throw new \BgaUserException($game->translate("Card is not at the Leader's location"));
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
                    throw new \BgaUserException($game->translate("There are available Character cards in your Approach Deck"));
                }
            }
            else
            {
                $cards = array_values(array_filter($cards, fn($card) => $card->Id == $id));
                if (count($cards) == 0)
                {
                    throw new \BgaUserException($game->translate("Invalid card id"));
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

            $this->setUsed($game->theah, true);

            $game->gamestate->nextState("cardChosen");
        }
    }
}