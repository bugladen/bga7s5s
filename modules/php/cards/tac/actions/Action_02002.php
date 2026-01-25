<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsPlayers;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02002 extends CharacterAction implements ISorcererAbility, IAbilityThatTargetsPlayers

{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Manipulate Top Cards of Player Faction Deck");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $elisabetta = $this->getOwningCharacter($theah);
        if ( ! $theah->cardInCity($elisabetta))
        {
            return false;
        }

        if ( ! $elisabetta->hasTrait("Sorcerer"))
        {
            return false;
        }        

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02002", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02002)
        {
            $players = $game->loadPlayersBasicInfos();
            $args["players"] = array_map(fn($player) => ['id' => $player['player_id'], 'name' => $player['player_name']], array_values($players));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02002_2 || $state == States::HIGH_DRAMA_PLAYER_TURN_02002_3)
        {
            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($opponentId);
            
            $args['opponentName'] = $opponentName;

            $cards = json_decode($game->globals->get(Game::CHOSEN_CARD));
            $args['cards'] = $cards;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02002)
        {
            $players = $game->loadPlayersBasicInfos();
            if (! isset($players[$id]) && $id != 0)
            {
                throw new \BgaUserException($game->translate("Invalid opponent"));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);

            $owner = $this->getOwningCard($game->theah);
            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($id, 3);

            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);
            $this->announceAction($game);

            $game->globals->set(Game::CHOSEN_CARD, json_encode($cards));

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $owner->Id);
            $game->theah->queueEvent($sorceryStartEvent);

            $game->gamestate->nextState("playerChosen");
        }

    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02002_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $deck = $game->getGameDeckObject();

            $playerId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $deckName = $game->getPlayerFactionDeckName($playerId);
            $discardName = $game->getPlayerDiscardDeckName($playerId);

            foreach ($ids as $id)
            {
                $card = $game->getCardObjectFromDb($id);
                if ($card == null)
                {
                    throw new \BgaUserException($game->translate("Invalid card id"));
                }

                if ($card->ControllerId != $playerId)
                {
                    throw new \BgaUserException(sprintf($game->translate("Card %s is not owned by the player"), $card->Name));
                }

                if ($card->Location != $deckName)
                {
                    throw new \BgaUserException(sprintf($game->translate("Card %s is not in the deck of the player"), $card->Name));
                }
            }

            $originalCards = json_decode($game->globals->get(Game::CHOSEN_CARD));

            foreach ($ids as $id)
            {
                $notification = "cardAddedToPlayerDiscardPile";

                $card = $game->getCardObjectFromDb($id);
                $game->notify->all($notification, clienttranslate('${card_inject_code} has been moved to the discard pile.'), [
                    "card_inject_code" => $card->getInjectCode(),
                    "playerId" => $playerId,
                    "card" => $card->getPropertyArray($game),
                ]);

                $deck->moveCard($id, $discardName);
                //Remove the card from the original cards
                $originalCards = array_filter($originalCards, fn($originalCard) => $originalCard->id != $id);
            }

            if (count($originalCards) == 0)
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState("allDiscarded");
            }
            else
            {
                $game->globals->set(Game::CHOSEN_CARD, json_encode(array_values($originalCards)));

                $game->gamestate->nextState("cardsChosen");
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02002_3)
        {
            $playerId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $deckName = $game->getPlayerFactionDeckName($playerId);
            $opponentName = $game->getPlayerNameById($playerId);
            $deck = $game->getGameDeckObject();

            $remainingCards = json_decode($game->globals->get(Game::CHOSEN_CARD));

            $remainingIds = array_map(fn($remainingCard) => $remainingCard->id, $remainingCards);

            foreach ($ids as $id) 
            {
                if (!in_array($id, $remainingIds))
                {
                    throw new \BgaUserException(sprintf($game->translate("Card %s is not in the remaining cards."), $id));
                }

                //Move card to top of deck
                $deck->insertCardOnExtremePosition((int)$id, $deckName, true);                
            }

            $owner = $this->getOwningCard($game->theah);
            $message = clienttranslate('${card_inject_code}: ${player_name} has chosen the order of the remaining cards in ${opponent_name}\'s Faction Deck.');
            $game->notify->all("message", $message, [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
                "opponent_name" => $opponentName,
            ]);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $owner->Id);
            $game->theah->queueEvent($sorceryPlayedEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}