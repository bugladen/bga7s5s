<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01069 extends CharacterAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Recover Attachment from Discard Pile");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $maxime = $this->getOwningCharacter($theah);
        if (! $maxime->HasTrait("Sorcerer"))
        {
            return false;
        }

        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        if (count($hand) == 0)
        {
            return false;
        }

        $discardPileName = $theah->game->getPlayerDiscardDeckName($playerId);
        $cards = $theah->getCardObjectsAtLocation($discardPileName);
        $cards = array_filter($cards, fn($card) => $card instanceof Attachment && ! $card->hasTrait("Unique"));

        return count($cards) > 0;
    }

    public function handleEvent(Event $event): void
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $discardPileName = $game->getPlayerDiscardDeckName($event->playerId);
            $cards = $event->theah->getCardObjectsAtLocation($discardPileName);
            $cards = array_filter($cards, fn($card) => $card instanceof Attachment && ! $card->hasTrait("Unique"));

            if (count($cards) == 0)
            {
                throw new \BgaUserException($game->translate("No Non-Unique attachments in your Discard Pile"));
            }

            $maxime = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $maxime->Id, '01069', $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01069)
        {
            $maxime = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $maxime->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01069_2)
        {
            $maxime = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $maxime->Id;

            $playerId = $maxime->ControllerId;
            $discardPileName = $game->getPlayerDiscardDeckName($playerId);
            $cards = $game->theah->getCardObjectsAtLocation($discardPileName);
            $cards = array_filter($cards, fn($card) => $card instanceof Attachment && ! $card->hasTrait("Unique"));

            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01069)
        {
            $maxime = $this->getOwningCharacter($game->theah);

            $card = $game->getCardObjectFromDb($id);

            if ($card->ControllerId != $maxime->ControllerId)
            {
                throw new \BgaUserException($game->translate("You do not control this card"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new \BgaUserException($game->translate("Card not in your hand"));
            }

            $deck = $game->getGameDeckObject();
            $deck->moveCard($card->Id, Game::LOCATION_PURGATORY);
            $game->globals->set(Game::CHOSEN_CARD, $card->Id);
            
            $game->gamestate->nextState("cardChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01069_2)
        {
            $card = $game->getCardObjectFromDb($id);
            $maxime = $this->getOwningCharacter($game->theah);
            $discardPileName = $game->getPlayerDiscardDeckName($maxime->ControllerId);

            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            if ($card->Location != $discardPileName)
            {
                throw new \BgaUserException($game->translate("Card not in your discard pile"));
            }

            if (! $card instanceof Attachment)
            {
                throw new \BgaUserException($game->translate("Card is not an attachment"));
            }

            if ($card->hasTrait("Unique"))
            {
                throw new \BgaUserException($game->translate("You cannot recover a unique attachment"));
            }

            $this->announceAction($game);

            // Get the card discarded from the previous step
            $discardedCardId = $game->globals->get(Game::CHOSEN_CARD);
            $discardedCard = $game->getCardObjectFromDb($discardedCardId);

            $owner = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($discardedCard->OwnerId, $discardedCard->Id, $owner->Id);
            $game->theah->queueEvent($discardEvent);

            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($maxime->ControllerId, $id);
            $game->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createCardAddedToHandEvent($maxime->ControllerId, $id);
            $game->theah->queueEvent($addEvent);

            $sorcererEvent = EventFactory::createSorcererAbilityPlayedEvent($maxime->ControllerId, $maxime->Id, $this->Id, $maxime->Id, $maxime->Location);
            $game->theah->queueEvent($sorcererEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($maxime->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("attachmentChosen");
        }
    }
}