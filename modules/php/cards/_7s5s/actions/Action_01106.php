<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01106_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01106 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Play Target Risk From Opponent's Discard Pile");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $opponents = $theah->game->loadPlayersBasicInfos();
        foreach ($opponents as $opponentId => $opponent)
        {
            if ($opponentId == $playerId)
            {
                continue;
            }

            $opponentDiscardName = $theah->game->getPlayerDiscardDeckName($opponentId);
            $opponentDeck = $theah->game->getGameDeckObject();
            $cardInfos = $opponentDeck->getCardsInLocation($opponentDiscardName);
            foreach ($cardInfos as $cardInfo)
            {
                $card = $theah->game->getCardObjectFromDb($cardInfo['id']);
                if ($card instanceof Risk)
                {
                    if ($card instanceof IHasActions)
                    {
                        $actions = $card->getActions();
                        foreach ($actions as $action)
                        {
                            if ($action->isAvailableToPlayer($playerId, $theah, $overrideInHandCheck = true))
                            {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01106", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01106)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01106_2)
        {
            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($opponentId);
            $args['opponentName'] = $opponentName;

            $discardName = $game->getPlayerDiscardDeckName($opponentId);
            $deck = $game->getGameDeckObject();
            $cardInfos = $deck->getCardsInLocation($discardName);
            $owner = $this->getOwningCard($game->theah);
            $cards = [];
            $actions = [];
            foreach ($cardInfos as $cardInfo)
            {
                $card = $game->getCardObjectFromDb($cardInfo['id']);
                if ($card instanceof Risk)
                {
                    $cards[] = $card->getPropertyArray($game);
                    if ($card instanceof IHasActions)
                    {
                        $cardActions = $card->getActions();
                        foreach ($cardActions as $action)
                        {
                            if ($action->isAvailableToPlayer($owner->ControllerId, $game->theah, $overrideInHandCheck = true))
                            {
                                $actions[] = $action->getPropertyArray($game);
                            }
                        }
                    }
                }
            }
            $args['cards'] = $cards;
            $args['actions'] = $actions;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01106)
        {
            $playerInfo = $game->loadPlayersBasicInfos();
            $playerIds = array_keys($playerInfo);
            if ( ! in_array($id, $playerIds))
            {
                throw new \BgaUserException($game->translate("Invalid opponent"));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);

            $game->gamestate->nextState("opponentChosen");
        }
    }

    public function actFromActionWithActionId(Game $game, int $state, string $stateName, int $actionSourceId, string $actionId): void
    {
        parent::actFromActionWithActionId($game, $state, $stateName, $actionSourceId, $actionId);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01106_2)
        {
            $riskCard = $game->theah->getCardById($actionSourceId);
            if ($riskCard instanceof IHasActions)
            {
                $action = $riskCard->getActionById($actionId);
            }

            //Place original card in special hiding location
            $deck = $game->getGameDeckObject();
            $deck->moveCard($riskCard->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            $moveEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($riskCard->ControllerId, $riskCard->Id);
            $game->theah->queueEvent($moveEvent);

            //Create a clone of the risk card
            $owner = $this->getOwningCard($game->theah);
            $card = $game->createCardInLocation('01106_RiskClone', Game::LOCATION_HAND, $owner->ControllerId, $owner->ControllerId);
            $card->Name = $riskCard->Name;
            $card->Image = $riskCard->Image;

            if ($riskCard instanceof IWealthCost)
            {
                $cost = $riskCard->getWealthCost();
            }
            if ($card instanceof IWealthCost)
            {
                $card->setWealthCost($cost);
            }

            $newAction = clone $action;
            if ($newAction instanceof ICardAbility)
            {
                $newAction->setId($card->Id);
                $newAction->setOwnerId($card->Id);
            }
            if ($card instanceof IHasActions)
            {
                $card->addAction($newAction, $game, $notify = false);
            }

            if ($card instanceof _01106_RiskClone)
            {
                $card->ClonedCardId = $riskCard->Id;
                $card->ParentCardId = $owner->Id;
            }

            $game->updateCardObjectInDb($card);

            $addEvent = EventFactory::createCardAddedToHandEvent($owner->ControllerId, $card->Id);
            $game->theah->queueEvent($addEvent);

            if ($newAction instanceof ICardAbility)
            {
                $newActionId = $newAction->getId();
                $game->globals->set(GAME::CHOSEN_ACTION, $newActionId);
                $game->globals->set(GAME::TRANSITION_INTERNAL_ID, $newActionId);
                $game->globals->set(GAME::ABNORMAL_FLOW, true);
                }
    
            if ($action->RequiresPerformerSelected)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01106_performer", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $riskCard->Id, Game::PAY_STATE_IN_HAND_ACTION);
                $game->theah->queueEvent($event);

                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01106_pay", $this->Id);        
                $game->theah->queueEvent($transition);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $actionResolvedEvent->priority = Event::TRANSITION_PRIORITY;
                $game->theah->queueEvent($actionResolvedEvent);
            }

            $game->gamestate->nextState("actionChosen");
        }
    }
}
