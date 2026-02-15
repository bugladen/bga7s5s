<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01186;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class _01186 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Maryam Benu Pleroma");
        $this->Image = "01186.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 186;

        $this->Title = 'Impervious Champion';

        $this->Resolve = 5;
        $this->Combat = 4;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost = 6;
        $this->CityCardNumber = 10;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Duelist',
            'Weapons Master',
            'Ashur',
        ];

        $this->resetCard();

        $this->Techniques = [
            new Technique_01186(),
        ];
    }

    public function handleEvent(Event $event)
    {
        //Maryams imperviousness supersedes the event
        //Handle each event from a Risk source that would target her and cancel them before they are processed.
        //Mark ImperviousnessUsedToday as true so that it cannot be used again until the next day.
        if ( ! $this->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED) && 
            (($event instanceof EventCardMoved && $event->cardId == $this->Id && $event->sourceId != 0) ||
            ($event instanceof EventCardEngaged && $event->cardId == $this->Id && $event->sourceId != 0))
        )
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters)
            {
                $this->addMaryamCondition($event->theah->game);

                $event->canceled = true;
                return;
            }
        }

        if ( ! $this->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED) && $event instanceof EventChallengeIssued && $event->defenderId == $this->Id && $event->sourceId != 0)
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters)
            {
                $this->addMaryamCondition($event->theah->game);

                $event->canceled = true;
                return;
            }
        }

        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->removeCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED);
            $event->theah->game->notify->all("maryamBenuPleromaAbilityRemoved", "", [
                "cardId" => $this->Id,
            ]);             
        }
    }

    public function addMaryamCondition(Game $game)
    {
        $this->addCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED);
        $game->notify->all("maryamBenuPleromaAbilityUsed", clienttranslate('Maryam has used her Imperviousness to block the ability targeting her.'), [
            "cardId" => $this->Id,
        ]);
    }
}