<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class _04cd07 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Festival of Fools');
        $this->Image = '04cd07.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->CityCardNumber = 7;

        $this->InPlayXImageOffset = 10;

        $this->Traits = [
            clienttranslate('Revelry')
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> When this card is revealed • Each player draws a card.</p>
<p><b>Forced:</b> At the end of High Drama • Each player with a character at this location that does not control this location draws a card.</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Forced: When this card is revealed • Each player draws a card.
        if ($event instanceof EventCityCardAddedToLocation && $event->cardId == $this->Id)
        {
            $theah = $event->theah;
            $game = $theah->game;
            $players = $game->loadPlayersBasicInfos();

            $game->notify->all("message", clienttranslate(
                '${card_inject_code}: Each player draws a card.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            foreach ($players as $playerId => $player)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($playerId, $this->getInjectCode());
                $theah->queueEvent($drawEvent);
            }

            return;
        }

        // Forced: At the end of High Drama • Each player with a character at this
        // location that does not control this location draws a card.
        if ($event instanceof EventHighDramaPhaseEnd && $event->theah->cardInCity($this))
        {
            $theah = $event->theah;
            $game = $theah->game;
            $location = $theah->getCityLocation($this->Location);
            $players = $game->loadPlayersBasicInfos();

            $drewAny = false;
            foreach ($players as $playerId => $player)
            {
                if ($playerId == $location->Controller)
                {
                    continue;
                }

                if (count($theah->getCharactersAtLocationByPlayerId($this->Location, $playerId)) === 0)
                {
                    continue;
                }

                if (! $drewAny)
                {
                    $game->notify->all("message", clienttranslate(
                        '${card_inject_code}: Each player with a character at ${location_name} who does not control it draws a card.'), [
                        'i18n' => ['location_name'],
                        'card_inject_code' => $this->getInjectCode(),
                        'location_name' => $this->Location,
                    ]);
                    $drewAny = true;
                }

                $drawEvent = EventFactory::createCardDrawnEvent($playerId, $this->getInjectCode());
                $theah->queueEvent($drawEvent);
            }
        }
    }
}
