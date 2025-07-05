<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class _01178 extends CityCharacter  
{
    public bool $AbilityUsed;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Carmella Vanessa Slavaggi');
        $this->Image = 'img/cards/7s5s/178.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 178;

        $this->Title = 'Lady V';

        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 5;        
        $this->CityCardNumber = 2;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Duelist',
            'Vodacce',
        ];

        $this->AbilityUsed = false;
    }

    public function canChallenge(): bool
    {
        return $this->isControlled() && ! $this->Engaged || ! $this->AbilityUsed;
    }

    public function canIntervene(): bool
    {
        return $this->isControlled() && ! $this->Engaged || ! $this->AbilityUsed;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterIntervened && $event->newTargetId == $this->Id && $this->Engaged)
        {
            $this->AbilityUsed = true;
            $this->IsUpdated = true;
        }

        if ($event instanceof EventChallengeIssued && $event->challengerId == $this->Id && $this->Engaged)
        {
            $this->AbilityUsed = true;
            $this->IsUpdated = true;
        }

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->AbilityUsed = false;
            $this->IsUpdated = true;
        }
    }
}