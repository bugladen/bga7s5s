<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01134 extends RiskAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Manipulate Cards in Opponent's Faction Deck");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInPlayByPlayerId($playerId);
        $performers = array_filter($performers, fn($performer) => $performer->hasTrait("Sorcerer"));
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInPlayByPlayerId($playerId);
        $performers = array_filter($performers, fn($performer) => $performer->hasTrait("Sorcerer"));
        return array_values($performers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01134", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01134)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01134_2 || $state == States::HIGH_DRAMA_PLAYER_TURN_01134_3)
        {
            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($opponentId);
            $args['opponentName'] = $opponentName;

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

            $cards = json_decode($game->globals->get(Game::CHOSEN_CARD));
            $args['cards'] = $cards;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01134_4)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01134)
        {
            $players = $game->loadPlayersBasicInfos();
            if (! isset($players[$id]))
            {
                throw new \BgaUserException($game->translate("Invalid opponent"));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);

            $owner = $this->getOwningCard($game->theah);
            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($id, 5);
            $cards = [];
            foreach ($deckCards as $deckCard)
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }
            $game->globals->set(Game::CHOSEN_CARD, json_encode($cards));

            $game->gamestate->nextState("opponentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01134_4)
        {
            if ($id == 1)
            {
                $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);

                $owner = $this->getOwningCard($game->theah);

                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $performerId, $owner->Id);
                $game->theah->queueEvent($engageEvent);

                $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($drawEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);
            }

            $game->gamestate->nextState("engageChosen");
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01134_2 || $state == States::HIGH_DRAMA_PLAYER_TURN_01134_4)
        {
            $game->gamestate->nextState("pass");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01134_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $deck = $game->getGameDeckObject($owner->ControllerId);

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
                $game->notify->all("cardAddedToPlayerDiscardPile", clienttranslate('${card_inject_code} has been moved to the discard pile.'), [
                    "card_inject_code" => $card->getInjectCode(),
                    "playerId" => $playerId,
                    "card" => $card->getPropertyArray($game),
                ]);

                $deck->moveCard($id, $discardName);
                //Remove the card from the original cards
                $originalCards = array_filter($originalCards, fn($originalCard) => $originalCard->id != $id);
            }

            $game->globals->set(Game::CHOSEN_CARD, json_encode(array_values($originalCards)));

            $game->gamestate->nextState("cardsChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01134_3)
        {
            $playerId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($playerId);
            $deckName = $game->getPlayerFactionDeckName($playerId);
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
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has chosen the order of the remaining cards in ${opponent_name}\'s Faction Deck.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
                "opponent_name" => $opponentName,
            ]);

            $game->gamestate->nextState("cardsSorted");
        }
    }

}