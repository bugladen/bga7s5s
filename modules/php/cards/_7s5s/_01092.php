<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01092;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01092 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Makepeace Botwighte");
        $this->Image = "img/cards/7s5s/092.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 92;

        $this->Faction = "Castille";
        $this->Title = "Gracious Cheat";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            "Diplomat",
            "Scoundrel",
            "Avalon",
        ];

        $this->Actions = [
            new Action_01092(),
        ];

        $this->resetCard();
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment, array &$explanations): int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment, $explanations);

        if ($performer->isNotControlledByPlayer($this->ControllerId) && $performer->Location == $this->Location && $theah->cardInCity($performer))
        {
            $discount -= 1;
            $explanations[] = sprintf($theah->game->translate("%s: +1 because performer is opposing Makepeace Botwighte."), $this->getInjectCode());
        }

        return $discount;
    }
}