<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01124_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01124 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Play Sorcery From Discard Pile");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $owner->HasTrait("Sorcerer"))
        {
            return false;
        }

        $cards = $theah->sorceryRisksAvailableFromDiscardPile($owner);
        return count($cards) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01124", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01124)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $args['performerId'] = $owner->Id;
            
            $cards = $game->theah->sorceryRisksAvailableFromDiscardPile($owner);
            $availableActions = [];
            foreach ($cards as $card)
            {
                $actions = $card->getActions();
                foreach ($actions as $action)
                {
                    if ($action->isAvailableToPlayer($owner->ControllerId, $game->theah, $overrideInHandCheck = true))
                    {
                        $availableActions[] = $action->getPropertyArray($game);
                    }
                }
            }
            $args['cards'] = array_map(fn($card) => $card->getPropertyArray($game), $cards);
            $args['actions'] = $availableActions;
        }
        
        return $args;
    }

    public function actFromActionWithActionId(Game $game, int $state, string $stateName, int $actionSourceId, string $actionId): void
    {
        parent::actFromActionWithActionId($game, $state, $stateName, $actionSourceId, $actionId);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01124)
        {
            $riskCard = $game->theah->getRiskById($actionSourceId);
            if ($riskCard == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $owner = $this->getOwningCard($game->theah);

            if ($riskCard->ControllerId != $owner->ControllerId)
            {
                throw new \BgaUserException($game->translate("Card is not controlled by you."));
            }

            $discardPileName = $game->getPlayerDiscardDeckName($owner->ControllerId);
            if ($riskCard->Location != $discardPileName)
            {
                throw new \BgaUserException($game->translate("Card is not in your Discard Pile"));
            }

            $handWealth = $game->handWealthCount($owner->ControllerId);

            if ($riskCard instanceof IHasActions)
            {
                $action = $riskCard->getActionById($actionId);

                if ( ! $action->isAvailableToPlayer($owner->ControllerId, $game->theah, $overrideInHandCheck = true))
                {
                    throw new \BgaUserException($game->translate("Action is not available to you."));
                }

                [$discount, $explanations] = $game->theah->getActionFromHandDiscount($owner, $action);
                $cost = $riskCard->WealthCost - $discount;
                if ($handWealth < $cost)
                {
                    throw new \BgaUserException(sprintf($game->translate("You do not have enough Wealth (%d) to pay for the Sorcery Risk."), $handWealth));
                }
            }
        
            //Place original card in special hiding location
            $deck = $game->getGameDeckObject();
            $deck->moveCard($riskCard->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            $moveEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($riskCard->ControllerId, $riskCard->Id);
            $game->theah->queueEvent($moveEvent);

            //Create a clone of the risk card
            $owner = $this->getOwningCard($game->theah);
            $card = $game->createCardInLocation('01124_RiskClone', Game::LOCATION_HAND, $owner->ControllerId, $owner->ControllerId);
            $card->Name = $riskCard->Name;
            $card->Image = $riskCard->Image;

            if ($card instanceof IWealthCost)
            {
                $cost = $riskCard->WealthCost;
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

            if ($card instanceof _01124_RiskClone)
            {
                $card->ClonedCardId = $riskCard->Id;
            }

            foreach ($riskCard->Traits as $trait)
            {
                $card->addTrait($game, $trait);
            }

            $game->updateCardObjectInDb($card);

            $sorceryEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($sorceryEvent);
    
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
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01124_performer", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $riskCard->Id, Game::PAY_STATE_IN_HAND_ACTION);
                $game->theah->queueEvent($event);

                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01124_pay", $this->Id);        
                $game->theah->queueEvent($transition);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $actionResolvedEvent->priority = Event::TRANSITION_PRIORITY;
                $game->theah->queueEvent($actionResolvedEvent);
            }

            $this->announceAction($game);
            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("actionChosen");        
        }
    }
}