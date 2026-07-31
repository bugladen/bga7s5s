<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd01_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
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

class Action_04cd01b extends AttachmentAction implements IAbilityThatTargetsCards
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Sink: Play Target Risk From Opponent's Discard Pile");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null || ! $theah->cardInCity($owner))
        {
            return false;
        }

        // WHY: Attachment is not leaving the hand — unlike Improvising (01106), do not
        // subtract the owning card's wealth from the affordability check.
        $handWealth = $theah->game->handWealthCount($playerId);

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
                if ($card instanceof Risk && $card instanceof IHasActions)
                {
                    $actions = $card->getActions();
                    foreach ($actions as $action)
                    {
                        // WHY: Skip self-typed actions to avoid unbounded recursion
                        // (same pattern as Action_01106).
                        if ($action instanceof self)
                        {
                            continue;
                        }
                        [$discount, $explanations] = $theah->getActionFromHandDiscount(null, $action);
                        $cost = $card->WealthCost - $discount;
                        if ($handWealth >= $cost && $action->isAvailableToPlayer($playerId, $theah, $overrideInHandCheck = true))
                        {
                            return true;
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
            $attachment = $this->getOwningAttachment($event->theah);
            $transition = EventFactory::createTransitionEvent(
                $attachment->ControllerId,
                $attachment->Id,
                "04cd01b",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD01B)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD01B_2)
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
            $handWealth = $game->handWealthCount($owner->ControllerId);
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
                            if ($action instanceof self)
                            {
                                continue;
                            }
                            [$discount, $explanations] = $game->theah->getActionFromHandDiscount(null, $action);
                            $cost = $card->WealthCost - $discount;
                            if ($handWealth >= $cost && $action->isAvailableToPlayer($owner->ControllerId, $game->theah, $overrideInHandCheck = true))
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD01B)
        {
            $playerInfo = $game->loadPlayersBasicInfos();
            $playerIds = array_keys($playerInfo);
            if (! in_array($id, $playerIds))
            {
                throw new UserException($game->translate("Invalid opponent"));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);

            $game->gamestate->nextState("opponentChosen");
        }
    }

    public function actFromActionWithActionId(Game $game, int $state, string $stateName, int $actionSourceId, string $actionId): void
    {
        parent::actFromActionWithActionId($game, $state, $stateName, $actionSourceId, $actionId);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD01B_2)
        {
            $riskCard = $game->theah->getCardById($actionSourceId);
            if ($riskCard instanceof IHasActions)
            {
                $action = $riskCard->getActionById($actionId);
            }

            $attachment = $this->getOwningAttachment($game->theah);
            $character = $this->getOwningCharacter($game->theah);
            $controllerId = $attachment->ControllerId;

            // Cost: Sink this card. Paid at commit so the player can still back out
            // of opponent/risk selection without losing the attachment.
            if ($attachment instanceof Attachment && $attachment->isAttached() && $character !== null)
            {
                $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                    $controllerId,
                    $character->Id,
                    $attachment->Id
                );
                $game->theah->queueEvent($unequipEvent);

                $removedEvent = EventFactory::createCardRemovedFromPlayEvent(
                    $controllerId,
                    $attachment->Id,
                    $attachment->Location
                );
                $game->theah->queueEvent($removedEvent);

                // WHY: Text says "sink", not discard — bottom of City Deck.
                $sinkEvent = EventFactory::createCardAddedToCityDeckEvent(
                    $controllerId,
                    $attachment->Id,
                    false
                );
                $game->theah->queueEvent($sinkEvent);
            }

            // Place original card in special hiding location
            $deck = $game->getGameDeckObject();
            $deck->moveCard($riskCard->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            $moveEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($riskCard->ControllerId, $riskCard->Id);
            $game->theah->queueEvent($moveEvent);

            // Create a clone of the risk card (mirrors Action_01106)
            $card = $game->createCardInLocation('04cd01_RiskClone', Game::LOCATION_HAND, $controllerId, $controllerId);
            $game->theah->addCardToWorld($card);
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
                $newAction->setOwnerId($card->Id);
            }
            if ($card instanceof IHasActions)
            {
                $card->addAction($newAction, $game, $notify = false);
            }

            if ($card instanceof _04cd01_RiskClone)
            {
                $card->ClonedCardId = $riskCard->Id;
            }

            $game->updateCardObjectInDb($card);

            $addEvent = EventFactory::createCardAddedToHandEvent($controllerId, $card->Id);
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
                $transition = EventFactory::createTransitionEvent($controllerId, $attachment->Id, "inHandActionChoosePerformer", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                // WHY: pass the clone's Id, not $riskCard->Id. Discount reactions key DiscountedCardId
                // off this event and match it to $action->OwnerId (the clone). Same fix as Action_01106/01124/01154.
                $event = EventFactory::createEnteringPayStateEvent($controllerId, $card->Id, Game::PAY_STATE_IN_HAND_ACTION, $newActionId);
                $game->theah->queueEvent($event);

                $transition = EventFactory::createTransitionEvent($controllerId, $attachment->Id, "inHandActionPay", $this->Id);
                $game->theah->queueEvent($transition);
            }

            // createActionResolvedEvent for this action is queued by _04cd01_RiskClone::handleEvent
            // after the cloned risk is discarded from hand — the action is only truly resolved once
            // the chosen risk has been paid for and played.

            $game->gamestate->nextState("actionChosen");
        }
    }
}
