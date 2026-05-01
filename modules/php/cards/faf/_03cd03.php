<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd03;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _03cd03 extends CityEventCard implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Chance Meeting');
        $this->Image = '03cd03.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;

        $this->CityCardNumber = 3;

        $this->Traits = [
            clienttranslate('Ad Hoc'),
            clienttranslate('Camaraderie'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Target a player • In order of Initiative, each player that controls fewer characters than the targeted player may Muster a character from their approach deck at this location. Discard this card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03cd03(),
        ];
    }
}
