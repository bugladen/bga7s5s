<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatDependsOnNotBeingFirstPlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01112b extends RiskAction implements IAbilityThatDependsOnNotBeingFirstPlayer
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard an Uncontrolled City Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $cards = $theah->getAllCards();
        $cards = array_filter($cards, fn($card) => $theah->cardInCity($card) && ! $card->isControlled() && $card instanceof ICityDeckCard && $card->canBeDiscardedFromCity());
        if (count($cards) == 0)
        {
            return false;
        }

        $forcedNotFirstPlayer = $theah->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);
        $isFirstPlayer = $theah->game->globals->get(Game::FIRST_PLAYER) == $playerId && ! $forcedNotFirstPlayer;
        return ! $isFirstPlayer;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $cards = $event->theah->getAllCards();
            $cards = array_filter($cards, fn($card) => $event->theah->cardInCity($card) && ! $card->isControlled() && $card instanceof ICityDeckCard && $card->canBeDiscardedFromCity());
            if (count($cards) == 0)
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
                $event->theah->queueEvent($actionResolvedEvent);
                return;
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01112", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01112)
        {
            $cards = $game->theah->getAllCards();
            $cards = array_values(array_filter($cards, fn($card) => $game->theah->cardInCity($card) && ! $card->isControlled() && $card instanceof ICityDeckCard && $card->canBeDiscardedFromCity()));
            $args["ids"] = array_map(fn($card) => $card->Id, $cards);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01112)
        {
            $card = $game->theah->getCardById($id);
            if ($card == null)
            {   
                throw new UserException($game->translate("Invalid card id"));
            }

            if (! $card instanceof ICityDeckCard)
            {
                throw new UserException($game->translate("Card is not a City Card"));
            }

            if ($card->isControlled())
            {
                throw new UserException($game->translate("Card is controlled"));
            }

            if (! $game->theah->cardInCity($card))
            {
                throw new UserException($game->translate("Card is not in the city"));
            }

            if (! $card->canBeDiscardedFromCity())
            {
                throw new UserException($game->translate("Card cannot be discarded"));
            }

            $owner = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($owner->ControllerId, $card->Id, $card->Location, $owner->Id, $asEffect = true);
            $game->theah->queueEvent($discardEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
        
}