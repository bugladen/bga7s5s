<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01035 extends Leader implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Kaspar Dietrich");
        $this->Image = "img/cards/7s5s/035.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 35;

        $this->Faction = "Eisen";
        $this->Title = "Old Iron";
        $this->Resolve = 9;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->Traits = [
            "Leader",
            "Hero",
            "General",
            "Eisen",
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01035(),
        ];
    }

    public function getParleyDiscount(Character $performer, bool $parleying) : int
    {
        $discount = parent::getParleyDiscount($performer, $parleying);

        if ($performer->Id == $this->Id && $parleying)
        {
            $discount += 2;
        }

        return $discount;
    }

}