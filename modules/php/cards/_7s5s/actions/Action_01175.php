<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01175 extends CardAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard any number of cards to heal a Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }


        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => !$character->hasTrait("Leader") && $character->Wounds > 0);
        if (count($characters) == 0)
        {
            return false;
        }

        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        return count($hand) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_values(array_filter($characters, fn($character) => !$character->hasTrait("Leader") && $character->Wounds > 0));
        return array_values($characters);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01175", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01175)
        {
            $owner = $this->getOwningCard($game->theah);
            foreach ($ids as $id)
            {
                $card = $game->getCardObjectFromDb($id);
                if ($card == null)
                {
                    throw new \BgaUserException(sprintf($game->translate("Card not found: %d"), $id));
                }
                if ($card->OwnerId != $owner->ControllerId)
                {
                    throw new \BgaUserException(sprintf($game->translate("Card is not owned by the performer: %d"), $id));
                }
                if ($card->Location != Game::LOCATION_HAND)
                {
                    throw new \BgaUserException(sprintf($game->translate("Card is not in the hand: %d"), $id));
                }
            }

            $wounds = count($ids);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer->Wounds < $wounds)
            {
                throw new \BgaUserException(sprintf($game->translate("Performer has only %d wound(s), but %d cards are being discarded."), $performer->Wounds, $wounds));
            }

            foreach ($ids as $id)
            {
                $discardEvent = EventFactory::createCardDiscardedFromHandEvent($owner->ControllerId, $id, $owner->Id);
                $game->theah->queueEvent($discardEvent);
            }

            $healEvent = EventFactory::createCharacterHealedEvent($performerId, $owner->Id, $wounds, $owner->getInjectCode());
            $game->theah->queueEvent($healEvent);

            $this->resetPlayerPassCount($game);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}