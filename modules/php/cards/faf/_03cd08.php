<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;

class _03cd08 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Inauguration Day');
        $this->Image = '03cd08.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;

        $this->CityCardNumber = 8;

        $this->Traits = [
            clienttranslate('Celebration'),
            clienttranslate('Revelry')
        ];

        $this->Text = clienttranslate("<b>Forced:</b> When a pressure occurs at this location • Count only the performer and en garde characters.");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPressureOccuring
            && $event->theah->cardInCity($this)
            && $event->location == $this->Location)
        {
            $game = $event->theah->game;

            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE);
            $game->globals->set(Game::CLAUD_ID, $this->Id);

            $game->notify->all("message", clienttranslate('${card_inject_code}: Only the Performer and En Garde Characters will be counted for this Pressure.'), [
                'card_inject_code' => $this->getInjectCode(),
            ]);
        }
    }
}
