<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01192 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Gustavo: Put a Revealed Risk Into Your Hand";
        $this->ShortName = "Put a Revealed Risk Into Your Hand";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {  
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $gustavo = $this->getOwningCard($theah);

        return $theah->cardInCity($gustavo);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $deck = $event->theah->game->getGameDeckObject();
            $gustavo = $this->getOwningCard($event->theah);
            if ($gustavo instanceof Character)
            {
                $count = $gustavo instanceof Character && $gustavo->ModifiedInfluence;
            }

            //Announce the Risks that are revealed
            $playerDeckName = $event->theah->game->getPlayerFactionDeckName($gustavo->ControllerId);
            $deckCards = $deck->getCardsOnTop($count, $playerDeckName, $gustavo->ControllerId);
            $names = [];
            $found = 0;
            foreach ($deckCards as $deckCard) {
                $card = $event->theah->game->getCardObjectFromDb($deckCard['id']);
                $names[] = $event->theah->game->translate($card->Name);
                if ($card instanceof Risk)
                    $found++;
            }

            $event->theah->game->notifyAllPlayers('message', clienttranslate('${player_name} uses ${card_name} to reveal ${count} Risks from their deck.  ${found} Risks have been revealed. (${names})'), [
                'i18n' => ['card_name'],
                'player_name' => $event->theah->game->getActivePlayerName(),
                'card_name' => $gustavo->Name,
                'count' => $count,
                'found' => $found,
                'names' => implode(', ', $names)
            ]);

            $this->setUsed($event->theah, true);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01192");
            $event->theah->queueEvent($transition);
        }
    }
    
    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01192)
        {
            $gustavo = $this->getOwningCard($game->theah);
            if ($gustavo instanceof Character)
            {
                $count = $gustavo->ModifiedInfluence;
            }

            $deck = $game->getGameDeckObject();
            $playerDeckName = $game->getPlayerFactionDeckName($gustavo->ControllerId);
            $deckCards = $deck->getCardsOnTop($count, $playerDeckName, $gustavo->ControllerId);
            $cards = [];
            foreach ($deckCards as $deckCard) {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $args["cards"] = $cards;
        }

        return $args;
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);
     
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01192)
        {
            $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses not to put any Risks into their Faction Hand.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01192)
        {
            $gustavo = $this->getOwningCard($game->theah);
            if ($gustavo instanceof Character)
            {
                $count = $gustavo->ModifiedInfluence;
            }

            $deck = $game->getGameDeckObject();
            $playerDeckName = $game->getPlayerFactionDeckName($gustavo->ControllerId);
            $deckCards = $deck->getCardsOnTop($count, $playerDeckName, $gustavo->ControllerId);

            $found = false;
            foreach ($deckCards as $deckCard) 
            {
                if ($deckCard['id'] == $id)
                {
                    $found = true;
                    break;
                }
            }

            if (!$found)
            {
                throw new \BgaUserException(sprintf($game->translate("Card %d is not in the top %d cards of the player's deck."), $id, $count));
            }

            $chosenCard = $game->getCardObjectFromDb($id);

            if (! $chosenCard instanceof Risk)
            {
                throw new \BgaUserException(sprintf($game->translate("Card %d is not a Risk."), $id));
            }
        
            // Put the chosen card into the player's hand. Sink the rest.
            foreach ($deckCards as $deckCard) 
            {
                if ($deckCard['id'] == $id)
                {
                    $removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($game->getActivePlayerId(), $id);
                    $game->theah->eventCheck($removeEvent);
                    
                    $addEvent = EventFactory::createCardAddedToHandEvent($game->getActivePlayerId(), $id);
                    $game->theah->eventCheck($addEvent);
        
                    $game->theah->queueEvent($removeEvent);
                    $game->theah->queueEvent($addEvent);
            
                }
                else
                {
                    $event = EventFactory::createCardAddedToFactionDeckEvent($game->getActivePlayerId(), $deckCard['id'], false);
                    $game->theah->queueEvent($event);
                }
            }

            $game->gamestate->nextState("cardChosen");
        }
    }
}
