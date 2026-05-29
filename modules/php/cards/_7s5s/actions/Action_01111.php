<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01111 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Choose Cards to Research");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        $discardName = $theah->game->getPlayerDiscardDeckName($owner->ControllerId);
        $cards = $theah->getCardObjectsAtLocation($discardName);

        $distinctNames = [];
        foreach ($cards as $card)
        {
            $distinctNames[$card->Name] = true;
        }

        return count($distinctNames) >= 3;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01111", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01111)
        {
            $owner = $this->getOwningCard($game->theah);
            $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);
            $cards = array_values($game->theah->getCardObjectsAtLocation($discardName));
            $args["cards"] = array_map(fn($card) => $card->getPropertyArray($game), $cards);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01111_2)
        {
            $opponents = [];
            $players = $game->loadPlayersBasicInfos();
            foreach ($players as $playerId => $player)
            {
                if ($playerId == $game->getActivePlayerId())
                {
                    continue;
                }

                $opponents[] = ['id' => $playerId, 'name' => $player['player_name']];
            }

            $args['opponents'] = $opponents;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01111_3)
        {
            $ids = $game->globals->get(Game::CHOSEN_CARD);
            $cards = [];
            foreach ($ids as $id)
            {
                $card = $game->getCardObjectFromDb($id);
                $cards[] = $card->getPropertyArray($game);
            }
            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01111)
        {
            if (count($ids) != 3)
            {
                throw new UserException($game->translate("You must choose three cards to research."));
            }

            $owner = $this->getOwningCard($game->theah);
            $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);

            $cards = [];
            foreach ($ids as $id)
            {
                $card = $game->getCardObjectFromDb($id);
                if ($card == null)
                {
                    throw new UserException(sprintf($game->translate("Card %d not found."), $id));
                }

                if ($card->Location != $discardName)
                {
                    throw new UserException(sprintf($game->translate("Card %d is not in your discard pile."), $id));
                }
                $cards[] = $card;
            }

            //Ensure the cards are unique by their Name property
            $uniqueCards = [];
            foreach ($cards as $card)
            {
                if (isset($uniqueCards[$card->Name]))
                {
                    throw new UserException(sprintf($game->translate("Chosen cards must have different names."), $card->Name));
                }
                $uniqueCards[$card->Name] = $card;
            }

            $game->globals->set(Game::CHOSEN_CARD, $ids);
            $game->gamestate->nextState();
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01111_2)
        {
            $players = $game->loadPlayersBasicInfos();
            if ( ! isset($players[$id]))
            {
                throw new UserException($game->translate("Invalid opponent"));
            }

            if ($id == $game->getActivePlayerId())
            {
                throw new UserException($game->translate("You cannot select yourself."));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);

            $owner = $this->getOwningCard($game->theah);
            $transition = EventFactory::createTransitionEvent($id, $owner->Id, "01111_3", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01111_3)
        {
            $owner = $this->getOwningCard($game->theah);
            $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);

            $chosenIds = $game->globals->get(Game::CHOSEN_CARD);
            if ( ! is_array($chosenIds) || ! in_array($id, $chosenIds))
            {
                throw new UserException($game->translate("You must choose one of the three cards selected to research."));
            }

            $card = $game->getCardObjectFromDb($id);
            if ($card == null)
            {
                throw new UserException(sprintf($game->translate("Card %d not found."), $id));
            }

            if ($card->Location != $discardName)
            {
                throw new UserException(sprintf($game->translate("Card %d is not in your discard pile."), $id));
            }

            $game->notify->all("message", clienttranslate('${opponent_name} chooses ${card_inject_code} for ${player_name} to research.'), [
                "opponent_name" => $game->getPlayerNameById($game->getActivePlayerId()),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($owner->ControllerId, $id);
            $game->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createCardAddedToHandEvent($owner->ControllerId, $id);
            $game->theah->queueEvent($addEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }

}