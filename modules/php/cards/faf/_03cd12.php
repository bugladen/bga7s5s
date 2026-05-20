<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class _03cd12 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Equal Claim');
        $this->Image = '03cd12.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;

        $this->CityCardNumber = 12;

        $this->Traits = [
            clienttranslate('Bureaucracy'),
            clienttranslate('Squabble'),
            clienttranslate('Compromise')
        ];

        $this->Text = clienttranslate("<b>Forced:</b> At the end of High Drama • If each player has an equal number of characters at this location, it becomes uncontrolled.");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventHighDramaPhaseEnd && $event->theah->cardInCity($this))
        {
            $theah = $event->theah;
            $location = $theah->getCityLocation($this->Location);

            if (! $location->isControlled())
            {
                return;
            }

            $players = $theah->game->loadPlayersBasicInfos();
            $counts = [];
            foreach ($players as $playerId => $_)
            {
                $counts[] = count($theah->getCharactersAtLocationByPlayerId($this->Location, $playerId));
            }

            if (count(array_unique($counts)) !== 1)
            {
                return;
            }

            if ($theah->canLocationBecomeUncontrolledBy($location->Controller, $this->Location))
            {
                $theah->game->notify->all("message", clienttranslate('${card_inject_code}: each player has an equal number of characters at ${location_name}. The location becomes uncontrolled.'), [
                    'i18n' => ['location_name'],
                    'card_inject_code' => $this->getInjectCode(),
                    'location_name' => $this->Location,
                ]);

                $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent($location->Controller, $this->Location);
                $theah->queueEvent($uncontrolledEvent);
            }
            else
            {
                $theah->game->notify->all("message", clienttranslate('${card_inject_code}: ${location_name} cannot become uncontrolled.'), [
                    'i18n' => ['location_name'],
                    'card_inject_code' => $this->getInjectCode(),
                    'location_name' => $this->Location,
                ]);
            }
        }
    }
}