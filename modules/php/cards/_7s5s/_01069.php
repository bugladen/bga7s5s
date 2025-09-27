<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01069;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;

class _01069 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Maxime De Lafayette");
        $this->Image = "img/cards/7s5s/069.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 69;

        $this->Faction = "Montaigne";
        $this->Title = "Bloody Socialite";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->Traits = [
            "Villain",
            "Sorcerer",
            "Montaigne",
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01069(),
        ];
    }

    public function handleEvent(Event $event)
    {        
        //Maxime ignores wounds from Sorceries and Sorcerer abilities he performs.
        if ($event instanceof EventCharacterWounded)
        {
            $ignoreWounds = false;
            $source = $event->theah->getCardById($event->sourceId);
            if ($source->Id == $this->Id)
            {
                //Check to see if ability is a Sorcerer ability
                $ability = $source->getAbilityById($event->abilityId);
                $ignoreWounds = ($ability && $ability instanceof ISorcererAbility) || $source->hasTrait("Sorcerer");
            }

            if ($ignoreWounds)
            {
                $event->theah->game->notifyAllPlayers("message", clienttranslate('${character_inject_code} ignores wounds from Sorceries and Sorcerer abilities he performs. ${wounds} wound(s) ignored from ${source_inject_code}.'), [
                    "character_inject_code" => $this->getInjectCode(),
                    "source_inject_code" => $source->getInjectCode(),
                    "wounds" => $event->wounds,
                ]);
            }
            else
            {
                parent::handleEvent($event);
            }
        }
        else
            parent::handleEvent($event);
                
    }

}