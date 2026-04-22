<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;

class _02044 extends Character
{
    public function __construct()
    {
        parent::__construct();
        
        
        $this->Name = clienttranslate('Solomonia Saboruvya');
        $this->Image = '02044.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 44;

        $this->initializeFaction('Ussura');
        $this->Title = clienttranslate('The Iron Will');
        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Aristocrat'),
            clienttranslate('Diplomat'),
            clienttranslate('Noble'),
            clienttranslate('Ussura'),
        ];

        $this->Text = clienttranslate("<p>While [The Forums] is uncontrolled, Solomonia cannot be challenged.</p><p>While Solomonia is at [The Forums], add +1 to your total during [Influence] pressures at adjacent locations.</p>");

        $this->resetCard();
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventChallengeIssued && $event->defenderId == $this->Id)
        {
            $forum = $event->theah->getCityLocation(Game::LOCATION_CITY_FORUM);
            if (!$forum->isControlled())
            {
                throw new \BgaUserException($event->theah->game->translate("Solomonia Saboruvya cannot be challenged while The Forums is uncontrolled."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPressureOccuring)
        {
            if (!$this->isControlled()) return;
            if ($this->Location != Game::LOCATION_CITY_FORUM) return;
            if (!in_array(Game::STAT_INFLUENCE, $event->pressureTypes)) return;

            $adjacentLocations = $event->theah->getAdjacentCityLocations(Game::LOCATION_CITY_FORUM, false);
            if (!in_array($event->location, $adjacentLocations)) return;

            $game = $event->theah->game;
            $game->notify->all("message", clienttranslate('${card_inject_code} will add +1 to ${player_name}\'s Influence total at the adjacent location.'), [
                'card_inject_code' => $this->getInjectCode(),
                'player_name' => $game->getPlayerNameById($this->ControllerId),
            ]);

            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::SOLOMONIA_PRESSURE_TYPE);
            $game->globals->set(Game::SOLOMONIA_ID, $this->Id);
        }
    }
}