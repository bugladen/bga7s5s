<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
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

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCityCardAddedToLocation && $event->card->Id == $this->Id) {
            $theah = $event->theah;
            $game = $theah->game;

            // Notify players that the player has lost reknown
            $game->notifyAllPlayers("message", clienttranslate(
                'Siren\'s Scream effect triggers. All players will transfer 1 Reknown to the card if able.'), []);

            //Each player will contribute a reknown to this card, if they have any
            $players = $game->loadPlayersBasicInfos();
            foreach ($players as $playerId => $player) 
            {   
                $reknown = $game->getPlayerReknown($playerId);
                if ($reknown > 0) {
    
                    //Player loses 1 reknown
                    $reknown = $theah->createEvent(Events::PlayerLosesReknown);
                    if ($reknown instanceof EventPlayerLosesReknown) {
                        $reknown->playerId = $playerId;
                        $reknown->amount = 1;
                    }
                    $theah->queueEvent($reknown);
    
                    // Add it to this card
                    $reknown = $theah->createEvent(Events::ReknownAddedToCard);
                    if ($reknown instanceof EventReknownAddedToCard) {
                        $reknown->cardId = $this->Id;
                        $reknown->amount = 1;
                    }
                    $theah->queueEvent($reknown);
                }
            }
        }

        if ($event instanceof EventReknownAddedToCard && $event->cardId == $this->Id) {
            $this->Reknown += $event->amount;
            $this->IsUpdated = true;

            $event->theah->game->notifyAllPlayers("reknownUpdatedOnCard", clienttranslate('${cardName} has ${amount} Reknown placed on it from effect.'), [
                "cardId" => $this->Id,
                "cardName" => $this->Name,
                "amount" => $this->Reknown,
            ]);
        }
    }
}