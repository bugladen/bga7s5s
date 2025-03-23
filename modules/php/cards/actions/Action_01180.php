<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01180 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Kaj Kousei: Equip Artifact from City Deck";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {  
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);

        return $theah->cardInCity($owner);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $deck = $event->theah->game->getGameDeckObject();
            $deckCards = $deck->getCardsOnTop(4, Game::LOCATION_CITY_DECK);
            $names = [];
            $count = 0;
            foreach ($deckCards as $deckCard) {
                $card = $event->theah->game->getCardObjectFromDb($deckCard['id']);
                $names[] = $card->Name;
                if (in_array('Artifact', $card->Traits))
                    $count++;
            }

            $event->theah->game->notifyAllPlayers('message', clienttranslate('${card_name} found ${count} Artifacts in the top 4 cards of the City Deck. (${names})'), [
                'card_name' => "<strong>{$this->Name}</strong>",
                'count' => $count,
                'names' => implode(', ', $names)
            ]);

            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition)
            {
                $transition->playerId = $event->playerId;
                $transition->transition = "01180";
                $transition->sourceId = $this->OwnerId;
            }
            $event->theah->queueEvent($transition);
        }
    }
}