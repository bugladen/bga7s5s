<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01205;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01205 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Giacinto");
        $this->Image = "01205.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 205;

        $this->Title = clienttranslate('Dogged Kidnapper');

        $this->InPlayXImageOffset = -20;

        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->WealthCost = 5;
        $this->CityCardNumber = 29;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Academic'),
            clienttranslate('Diplomat'),
            clienttranslate('Scoundrel'),
            clienttranslate('Castille'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p><b>City Action:</b> Engage Giacinto • Engage target opposing character. Move them and Giacinto to the same adjacent City location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01205(),
        ];
    }
}