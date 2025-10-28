<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01112b extends RiskAction
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
        $cards = array_filter($cards, fn($card) => $theah->cardInCity($card) && ! $card->isControlled() && $card instanceof ICityDeckCard);
        if (count($cards) == 0)
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        $firstPlayerId = $theah->game->globals->get(Game::FIRST_PLAYER, false);
        return $owner->ControllerId != $firstPlayerId;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
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
            $cards = array_values(array_filter($cards, fn($card) => $game->theah->cardInCity($card) && ! $card->isControlled() && $card instanceof ICityDeckCard));
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
                throw new \BgaUserException($game->translate("Invalid card id"));
            }

            if (! $card instanceof ICityDeckCard)
            {
                throw new \BgaUserException($game->translate("Card is not a City Card"));
            }

            if ($card->isControlled())
            {
                throw new \BgaUserException($game->translate("Card is controlled"));
            }

            if (! $game->theah->cardInCity($card))
            {
                throw new \BgaUserException($game->translate("Card is not in the city"));
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