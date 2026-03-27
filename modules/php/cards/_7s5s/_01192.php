<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01192;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01192 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Gustavo');
        $this->Image = "01192.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 192;
        
        $this->Title = clienttranslate('Humble Powerbroker');

        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->WealthCost = 4;
        $this->CityCardNumber = 16;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Academic'),
            clienttranslate('Diplomat'),
            clienttranslate('Villain'),
            clienttranslate('Castille'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>City Action: Reveal cards from your deck equal to Gustavo's [Inf]. Put a revealed risk into your hand. Sink the rest.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01192(),
        ];
    }
}