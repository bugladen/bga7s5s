<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01183 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("So It Begins");
        $this->Image = "img/cards/7s5s/183.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 183;

        $this->CityCardNumber = 7;

        $this->Traits = [
            'Brawl',
            'Feud',
        ];

        $this->resetCard();
    }

    public function getPressureStats(Theah $theah, ?Character $performer, string $location, Array &$pressureTypes): void
    {
        parent::getPressureStats($theah, $performer, $location, $pressureTypes);

        if ($location == $this->Location) 
        {
            $theah->game->notify->all("message", clienttranslate('${card_inject_code} will add Combat to the Pressure.'), [
                'card_inject_code' => $this->getInjectCode(),
            ]);
            $pressureTypes[] = Game::STAT_COMBAT;
        }
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        //Reduce duel parry by 1 if the card is in the same location as the actor or adversary
        if ($event instanceof EventDuelCalculateCombatCardStats && $event->theah->cardInCity($this)) 
        {
            $actor = $event->theah->getCardById($event->actorId);
            $adversary = $event->theah->getCardById($event->adversaryId);

            if ($actor->Location == $this->Location || $adversary->Location == $this->Location) 
            {
                $event->parry -= 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s: -1 Parry for being at same location"), $this->getInjectCode());
            }
        }
        
    }
    
}