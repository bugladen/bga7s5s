<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

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
        $this->Name = clienttranslate("Put a Revealed Risk Into Your Hand");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {  
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $gustavo = $this->getOwningCard($theah);

        if (! $gustavo->isControlled())
        {
            return false;
        }

        return $theah->cardInCity($gustavo);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $deck = $event->theah->game->getGameDeckObject();
            $gustavo = $this->getOwningCharacter($event->theah);
            $count = $gustavo->ModifiedInfluence;

            $this->announceAction($event->theah->game);

            //Announce the Risks that are revealed
            $deckCards = $event->theah->game->getCardsOnTopOfPlayerFactionDeck($gustavo->ControllerId, $count);
            $names = [];
            $found = 0;
            foreach ($deckCards as $deckCard) {
                $card = $event->theah->game->getCardObjectFromDb($deckCard['id']);
                if ($card instanceof Risk)
                {
                    $names[] = $card->getInjectCode();
                    $found++;
                }
            }

            $event->theah->game->notify->all('message', clienttranslate('${player_name} uses Gustavo\'s Action to reveal Risks from their deck.  ${found} Risks have been revealed. (${names})'), [
                'i18n' => ['card_name'],
                'player_name' => $event->theah->game->getPlayerNameById($gustavo->ControllerId),
                'card_name' => $gustavo->Name,
                'count' => $count,
                'found' => $found,
                'names' => implode(', ', $names)
            ]);

            $event->theah->game->globals->set(Game::MULTI_STATE_INITIATING_PLAYER, $gustavo->ControllerId);

            $this->setUsed($event->theah, true);
            $this->resetPlayerPassCount($event->theah->game);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01192", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }
    
    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01192 || $state == States::HIGH_DRAMA_PLAYER_TURN_01192_3)
        {
            $gustavo = $this->getOwningCharacter($game->theah);
            $count = $gustavo->ModifiedInfluence;

            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($gustavo->ControllerId, $count);
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
     
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01192_3)
        {
            $gustavo = $this->getOwningCharacter($game->theah);

            $game->notify->all("message", clienttranslate('${player_name} chooses not to put any Risks into their Faction Hand. The cards have been sunk.'), [
                "player_name" => $game->getPlayerNameById($gustavo->ControllerId),
            ]);

            $count = $gustavo->ModifiedInfluence;

            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($gustavo->ControllerId, $count);
            foreach ($deckCards as $deckCard) 
            {
                $event = EventFactory::createCardAddedToFactionDeckEvent($gustavo->ControllerId, $deckCard['id'], false);
                $game->theah->queueEvent($event);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($gustavo->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01192_3)
        {
            $gustavo = $this->getOwningCard($game->theah);
            if ($gustavo instanceof Character)
            {
                $count = $gustavo->ModifiedInfluence;
            }

            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($gustavo->ControllerId, $count);

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

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($gustavo->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cardChosen");
        }
    }
}
