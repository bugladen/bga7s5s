<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01038 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Reveal Attachments");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $otto = $this->getOwningCharacter($theah);
        return $theah->cardInCity($otto);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $deck = $event->theah->game->getGameDeckObject();
            $otto = $this->getOwningCharacter($event->theah);
            $count = 3;

            //Announce the cards revealed
            $deckCards = $event->theah->game->getCardsOnTopOfPlayerFactionDeck($otto->ControllerId, $count);
            $names = [];
            $found = 0;
            foreach ($deckCards as $deckCard) {
                $card = $event->theah->game->getCardObjectFromDb($deckCard['id']);
                if ($card instanceof Attachment)
                    $found++;
                $names[] = $card->getInjectCode();
            }

            $event->theah->game->notify->all('message', clienttranslate('${player_name} uses Otto Streit\'s Action to reveal Attachments from their deck. ${found} Attachment(s) have been revealed. 
            <p>Cards Revealed: ${names}'), [
                'i18n' => ['card_name'],
                'player_name' => $event->theah->game->getActivePlayerName(),
                'card_name' => $otto->Name,
                'count' => $count,
                'found' => $found,
                'names' => implode(', ', $names)
            ]);

            $this->setUsed($event->theah, true);
            $this->resetPlayerPassCount($event->theah->game);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01038", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01038 || $state == States::HIGH_DRAMA_PLAYER_TURN_01038_3)
        {
            $otto = $this->getOwningCharacter($game->theah);
            $count = 3;

            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($otto->ControllerId, $count);
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
     
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01038_3)
        {
            $game->notify->all("message", clienttranslate('${player_name} chooses not to put any Attachments into their Faction Hand.  The cards have been sunk.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);

            $count = 3;
            $otto = $this->getOwningCharacter($game->theah);
            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($otto->ControllerId, $count);
            foreach ($deckCards as $deckCard) 
            {
                $event = EventFactory::createCardAddedToFactionDeckEvent($otto->ControllerId, $deckCard['id'], false);
                $game->theah->queueEvent($event);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($otto->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01038_3)
        {
            $otto = $this->getOwningCharacter($game->theah);

            $count = 3;
            $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($otto->ControllerId, $count);

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

            if (! $chosenCard instanceof Attachment)
            {
                throw new \BgaUserException(sprintf($game->translate("Card %d is not an Attachment."), $id));
            }
        
            // Put the chosen card into the player's hand. Sink the rest.
            foreach ($deckCards as $deckCard) 
            {
                if ($deckCard['id'] == $id)
                {
                    $removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($otto->ControllerId, $id);
                    $game->theah->eventCheck($removeEvent);
                    
                    $addEvent = EventFactory::createCardAddedToHandEvent($otto->ControllerId, $id);
                    $game->theah->eventCheck($addEvent);
        
                    $game->theah->queueEvent($removeEvent);
                    $game->theah->queueEvent($addEvent);
            
                }
                else
                {
                    $event = EventFactory::createCardAddedToFactionDeckEvent($otto->ControllerId, $deckCard['id'], false);
                    $game->theah->queueEvent($event);
                }
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($otto->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cardChosen");
        }
    }
}