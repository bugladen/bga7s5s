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

        $this->Name = clienttranslate("Maxime de Lafayette");
        $this->Image = "img/cards/7s5s/069.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 69;

        $this->initializeFaction("Montaigne");
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
        if (! ($event instanceof EventCharacterWounded))
        {
            parent::handleEvent($event);
        }

        //Maxime ignores wounds from Sorceries and Sorcerer abilities he performs.
        if ($event instanceof EventCharacterWounded)
        {
            $ignoreWounds = false;
            if ($event->characterId != $this->Id || $event->sourceId == 0)
            {
                parent::handleEvent($event);
                return;
            }

            $source = $event->theah->getCardById($event->sourceId);
            if ($source?->Id == $this->Id || $source?->ControllerId == $this->ControllerId)
            {
                $sorcererAbility = false;
                if ($event->abilityId != '')
                {
                    $ability = $source->getAbilityById($event->abilityId);
                    if ($ability && $ability instanceof ISorcererAbility)
                    {
                        $sorcererAbility = true;
                    }
                }
                else
                {
                    if ($source instanceof ISorcererAbility)
                    {
                        $sorcererAbility = true;
                    }
                }

                $ignoreWounds = $sorcererAbility || $source->hasTrait("Sorcerer");
            }

            if ($ignoreWounds)
            {
                $event->theah->game->notify->all("message", clienttranslate('${character_inject_code} ignores wounds from Sorceries and Sorcerer abilities he performs. ${wounds} wound(s) ignored from ${source_inject_code}.'), [
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
    }

}