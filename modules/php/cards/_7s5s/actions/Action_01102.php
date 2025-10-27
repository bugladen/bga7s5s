<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01102 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard Two cards, Destroy This Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $deck = $theah->game->getGameDeckObject();
        $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        
        return count($hand) >= 2;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01102", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01102)
        {
            if (count($ids) != 2)
            {
                throw new \BgaUserException($game->translate("You must discard two cards."));
            }

            foreach ($ids as $id)
            {
                $card = $game->theah->getCardById($id);
                if ($card == null)
                {
                    throw new \BgaUserException($game->translate("Invalid card id."));
                }

                if ($card->Location != Game::LOCATION_HAND)
                {
                    throw new \BgaUserException($game->translate("Card is not in your hand."));
                }
            }

            foreach ($ids as $id)
            {
                $card = $game->theah->getCardById($id);
                $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $id, $card->Id, false, false, false);
                $game->theah->queueEvent($event);
            }

            $owner = $this->getOwningCard($game->theah);
            if ($owner instanceof IRiskAttachment)
            {
                $owner->removeRiskAttachment($game->theah);
            }

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $game->gamestate->nextState("cardsChosen");
        }
    }
}