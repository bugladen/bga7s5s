<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01179;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToCard;

class _01179 extends CityEventCard implements IHasActions
{
    use ActionTrait;

    public int $Reknown;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Siren's Scream");
        $this->Image = "01179.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 179;

        $this->CityCardNumber = 3;

        $this->Reknown = 0;

        $this->Traits = [
            clienttranslate("Catastrophe"),
            clienttranslate("Monster")
        ];

        $this->Text = clienttranslate("<p>Forced: When this card is revealed • Each player spends a Renown to it.</p><p>This card can only be discarded if it has no Renown. (Even during Dusk.)</p><p>City Action: Engage your performer • Take a Renown from this card. (Each player may activate this ability once per Day.)</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01179(),
        ];
    }

    public function eventCheck($event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventCardAddedToCityDiscardPile && $event->cardId == $this->Id && $this->Reknown > 0)
            throw new \BgaUserException($event->theah->game->translate("Siren's Scream will not be discarded while it has Renown on it."));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Card played to city
        if ($event instanceof EventCityCardAddedToLocation && $event->cardId == $this->Id) {
            $theah = $event->theah;
            $game = $theah->game;

            // Notify players that the player has lost reknown
            $game->notify->all("message", clienttranslate(
                '${card_inject_code} effect triggers. All players will transfer 1 Renown to the card if able.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            //Each player will contribute a reknown to this card, if they have any
            $players = $game->loadPlayersBasicInfos();
            foreach ($players as $playerId => $player) 
            {   
                $reknown = $game->getPlayerReknown($playerId);
                if ($reknown > 0) {
    
                    //Player loses 1 reknown
                    $reknownEvent = EventFactory::createPlayerLosesReknownEvent($playerId, 1);
                    $theah->queueEvent($reknownEvent);
    
                    // Add it to this card
                    $reknown = $theah->createEvent(Events::ReknownAddedToCard);
                    if ($reknown instanceof EventReknownAddedToCard) {
                        $reknown->playerId = $playerId;
                        $reknown->cardId = $this->Id;
                        $reknown->amount = 1;
                    }
                    $theah->queueEvent($reknown);
                }
            }
        }
    }
}