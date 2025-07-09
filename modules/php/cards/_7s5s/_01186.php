<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_01186;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class _01186 extends CityCharacter implements IHasTechniques
{
    use TechniqueTrait;

    public bool $ImperviousnessUsedToday = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Maryam Benu Pleroma");
        $this->Image = "img/cards/7s5s/186.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 186;

        $this->Title = 'Impervious Champion';

        $this->Resolve = 5;
        $this->Combat = 4;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 6;
        $this->CityCardNumber = 10;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Duelist',
            'Weapons Master',
            'Ashur',
        ];

        $this->Techniques = [
            new Technique_01186(),
        ];
        
        $this->ImperviousnessUsedToday = false;
    }

    public function handleEvent(Event $event)
    {
        //Maryams imperviousness supersedes the event
        //Handle each event from a Risk source that would target her and cancel them before they are processed.
        //Mark ImperviousnessUsedToday as true so that it cannot be used again until the next day.
        if ( ! $this->ImperviousnessUsedToday && 
            (($event instanceof EventCardMoved && $event->cardId == $this->Id && $event->sourceId != 0) ||
            ($event instanceof EventCardEngaged && $event->cardId == $this->Id && $event->sourceId != 0))
        )
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source instanceof Risk)
            {
                $this->ImperviousnessUsedToday = true;
                $maryam = $event->theah->getCardById($this->Id);
                $maryam->IsUpdated = true;
                $event->theah->game->notifyAllPlayers("message", clienttranslate('Maryam has used her Imperviousness to block the movement targeted at her.'), []);

                $event->canceled = true;
                return;
            }
        }

        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->ImperviousnessUsedToday = false;
            $this->IsUpdated = true;
        }
    }
}