<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01116b extends CardReaction
{
    private bool $IsActive = false;
    private int $PayStateType = 0;
    private int $DiscountedCardId = 0;
    private string $InternalId = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Receive a Discount of -1 when paying for non-Character cards";
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose activate a Discount of -1 when paying for non-Character cards: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Activate'), 'activate');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventEnteringPayState && $this->isAvailable())
        {        
            $yevgeni = $this->getOwningCharacter($event->theah);
            if ($event->playerId == $yevgeni->ControllerId)
            {
                $card = $event->theah->game->getCardObjectFromDb($event->cardId);
                if (! $card instanceof Character && $card instanceof IWealthCost && $card->getWealthCost() > 0)
                {
                    $this->PayStateType = $event->payStateType;
                    $this->DiscountedCardId = $event->cardId;
                    $this->InternalId = $event->internalId;
                    $owner = $this->getOwningCard($event->theah);
                    $owner->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($yevgeni->ControllerId, $yevgeni->Id, $this->Id);
                    $event->theah->stackEvent($transition);
                }
            }
        }

        if (($event instanceof EventPlayerTurnEnd || $event instanceof EventActionResolved || $event instanceof EventDuelEndOfRound) && $this->IsActive)
        {
            $this->IsActive = false;
            $this->DiscountedCardId = 0;
            $this->PayStateType = 0;

            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;        
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "activate")
        {
            $owner = $this->getOwningCard($game->theah);
            $this->IsActive = true;

            $game->globals->set(Game::ABNORMAL_FLOW, true);

            $this->setUsed($game->theah, true);
            $game->theah->addCardToWorld($owner);

            [$discount, $explanations] = $game->theah->calculateInHandPayDiscount($owner->ControllerId, $this->PayStateType, $this->DiscountedCardId, $this->InternalId);
        }

        $game->gamestate->nextState("done");
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, Array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        if ($this->IsActive && $action->OwnerId == $this->DiscountedCardId)
        {
            $owner = $this->getOwningCard($theah);
            if ($owner && $performer && $owner->ControllerId == $performer->ControllerId)
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s: -1 because Reaction is active."), $owner->getInjectCode());
            }
        }       

        return $discount;
    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $requestedReaction, Array &$explanations): int
    {
        $discount = parent::getReactionFromHandDiscount($theah, $requestedReaction, $explanations);

        if ($this->IsActive && $requestedReaction->OwnerId == $this->DiscountedCardId)
        {
            $owner = $this->getOwningCard($theah);
            $reactionOwner = $requestedReaction->getOwningCard($theah);
            if ($owner->ControllerId == $reactionOwner->ControllerId)
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s: -1 because Reaction is active."), $owner->getInjectCode());
            }
        }
        return $discount;
    }

    public function getEquipDiscount(Theah $theah, ?Character $performer, Attachment $attachment, Array &$explanations): int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment, $explanations);

        if ($this->IsActive && $attachment->Id == $this->DiscountedCardId)
        {
            $owner = $this->getOwningCard($theah);
            if ($owner->ControllerId == $performer->ControllerId)
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s: -1 because Reaction is active."), $owner->getInjectCode());
            }
        }

        return $discount;
    }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, Array &$explanations): int
    {
        $discount = parent::getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);

        if ($this->IsActive && $combatCard->Id == $this->DiscountedCardId)
        {
            $actor = $theah->getDuelRoundActor();
            $owner = $this->getOwningCard($theah);
            if ($owner->ControllerId == $actor->ControllerId)
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s: -1 because Reaction is active."), $owner->getInjectCode());
            }
        }
        return $discount;
    }
}