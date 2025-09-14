<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;

class _01188 extends CityCharacter
{
    public bool $HasIntervened = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Vladislav Novikoff');
        $this->Image = "img/cards/7s5s/188.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 188;
        
        $this->Title = 'Gentle Giant';

        $this->Resolve = 5;
        $this->Combat = 0;
        $this->Finesse = 0;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->resetModifiedCharacterStats();

        $this->ModifiedInfluence = 0;

        $this->WealthCost = 4;
        $this->CityCardNumber = 12;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Usurra',
        ];

        $this->HasIntervened = false;
    }

    public function canIntervene() : bool
    {
        if (!parent::canIntervene())
            return false;

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        //Mark that Vladislav has been used if he intervenes
        if ($event instanceof EventCharacterIntervened && $event->newTargetId == $this->Id) 
        {
            $this->HasIntervened = true;
            $this->IsUpdated = true;
        }

        //If this is the first round of the duel and Vladislav is the actor, set the duel type to Vladislav
        //This will cause the options for the player to have one choice - to end the duel
        if ($event instanceof EventDuelNewRound && $this->HasIntervened && $event->round == 1 && $event->actorId == $this->Id) 
        {
            $event->theah->game->notifyAllPlayers('message', clienttranslate('Vladislav Novikoff has intervened, so the duel will end immediately.'), []);
            $event->theah->game->globals->set(Game::DUEL_TYPE, Game::VLADISLAV_DUEL_TYPE);
        }

        //If the duel has ended and Vladislav has intervened, remove the intervention
        if ($event instanceof EventDuelEnd && $this->HasIntervened)
        {
            $this->HasIntervened = false;
            $this->IsUpdated = true;
        }
    }

}