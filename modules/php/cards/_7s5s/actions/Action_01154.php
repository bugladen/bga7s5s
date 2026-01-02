<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01154_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01154 extends AttachmentAction implements ISorcererAbility, IAbilityThatTargetsCards
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Play Risk from Discard Pile");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $character = $this->getOwningCharacter($theah);
        if ($character->Engaged)
            return false;

        $cards = $theah->risksAvailableFromDiscardPile($character);
        return count($cards) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $character = $this->getOwningCharacter($event->theah);
            $owner = $this->getOwningAttachment($event->theah);
            $engagedEvent = EventFactory::createCardEngagedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
            $event->theah->queueEvent($engagedEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01154", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01154)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $args['performerId'] = $owner->Id;
            
            $cards = $game->theah->risksAvailableFromDiscardPile($owner);
            $availableActions = [];
            foreach ($cards as $card)
            {
                $actions = $card->getActions();
                foreach ($actions as $action)
                {
                    $performers = $action->getPerformersForAction($owner->ControllerId, $game->theah);
                    $performers = array_values(array_filter($performers, fn($performer) => $performer->Id == $owner->Id));
                    if (count($performers) > 0)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01154)
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

                $performer = $this->getOwningCharacter($game->theah);
                [$discount, $explanations] = $game->theah->getActionFromHandDiscount($performer, $action);
                $cost = $riskCard->WealthCost - $discount;
                if ($handWealth < $cost)
                {
                    throw new \BgaUserException(sprintf($game->translate("You do not have enough Wealth (%d) to pay for the Sorcery Risk."), $handWealth));
                }
            }

            $game->globals->set(Game::CHOSEN_ACTION, $actionId);
            $game->globals->set(Game::CHOSEN_CARD, $riskCard->Id);

            $this->announceAction($game);
            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $sorceryEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $owner->Id);
            $game->theah->queueEvent($sorceryEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01154_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();        
        }
    }

    public function stateFromAction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01154_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $riskCard = $game->theah->getRiskById($game->globals->get(Game::CHOSEN_CARD));

            //Place original card in special hiding location
            $deck = $game->getGameDeckObject();
            $deck->moveCard($riskCard->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            $moveEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($riskCard->ControllerId, $riskCard->Id);
            $game->theah->queueEvent($moveEvent);

            //Create a clone of the risk card
            $card = $game->createCardInLocation('01154_RiskClone', Game::LOCATION_HAND, $owner->ControllerId, $owner->ControllerId);
            $card->Name = $riskCard->Name;
            $card->Image = $riskCard->Image;

            if ($card instanceof IWealthCost)
            {
                $cost = $riskCard->WealthCost;
                $card->setWealthCost($cost);
            }

            $actionId = $game->globals->get(Game::CHOSEN_ACTION);
            if ($riskCard instanceof IHasActions)
            {
                $action = $riskCard->getActionById($actionId);
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

            if ($card instanceof _01154_RiskClone)
            {
                $card->ClonedCardId = $riskCard->Id;
                $card->AttachmentId = $owner->Id;
            }

            foreach ($riskCard->Traits as $trait)
            {
                $card->addTrait($game, $trait, $quietly = true);
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
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "inHandActionChoosePerformer", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $riskCard->Id, Game::PAY_STATE_IN_HAND_ACTION, $newActionId);
                $game->theah->queueEvent($event);

                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "inHandActionPay", $this->Id);        
                $game->theah->queueEvent($transition);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $actionResolvedEvent->priority = Event::CHANGE_ACTIVE_PLAYER_PRIORITY;
            $game->theah->queueEvent($actionResolvedEvent);

            $sorceryEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($sorceryEvent);
    
            $game->gamestate->nextState();
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01154)
        {
            $owner = $this->getOwningAttachment($game->theah);
            $game->notify->all("message", clienttranslate('${player_name} has canceled out of ${attachment_inject_code}: [${action_name}] due to no available actions. 
            Since the performer had to engage to set up this action, the performer has been en garded and the player gains an extra action.'), [
                "i18n" => ["action_name"],
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "action_name" => $this->Name,
                "attachment_inject_code" => $owner->getInjectCode(),
            ]);
            $game->globals->set(Game::EXTRA_ACTIONS, 1);

            $character = $this->getOwningCharacter($game->theah);
            $owner = $this->getOwningAttachment($game->theah);
            $engardeEvent = EventFactory::createCardEngardedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engardeEvent);

            $game->gamestate->nextState();
        }
    }
}