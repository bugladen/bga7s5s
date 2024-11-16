<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToCard;

class _01179 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Siren's Scream";
        $this->Image = "img/cards/7s5s/179.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 179;

        $this->CityCardNumber = 3;
    }

    public function immediateEffect($theah)
    {
        parent::immediateEffect($theah);

        $game = $theah->game;

        //Each player will contribute a reknown to this card, if they have any
        $players = $game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player) {

            // Notify players that the player has lost reknown
            $game->notifyAllPlayers("sirensScream", clienttranslate(
                'Siren\'s Scream effect triggers. All players will transfer 1 Reknown to the card if able.'), []);
            $reknown = $game->getPlayerReknown($playerId);
            if ($reknown > 0) {

                //Player loses 1 reknown
                $event = $theah->createEvent(Events::PLAYER_LOSES_REKNOWN);
                if ($event instanceof EventPlayerLosesReknown) {
                    $event->priority = Event::LOW_PRIORITY;
                    $event->playerId = $playerId;
                    $event->amount = 1;
                }
                $theah->queueEvent($event);

                // Add it to this card
                $event = $theah->createEvent(Events::REKNOWN_ADDED_TO_CARD);
                if ($event instanceof EventReknownAddedToCard) {
                    $event->priority = Event::LOW_PRIORITY;
                    $event->cardId = $this->Id;
                    $event->amount = 1;
                }
                $theah->queueEvent($event);
            }
        }
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventReknownAddedToCard && $event->cardId == $this->Id) {
            $this->Reknown += $event->amount;
            $this->IsUpdated = true;

            $event->theah->game->notifyAllPlayers("reknownUpdatedOnCard", clienttranslate('${cardName} - ${amount} Reknown placed on it.'), [
                "cardId" => $this->Id,
                "cardName" => $this->Name,
                "amount" => $this->Reknown,
            ]);
        }
    }
}