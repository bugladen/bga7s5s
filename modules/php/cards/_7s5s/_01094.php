<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01094;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01094 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("'Padre' Aníbal");
        $this->Image = "01094.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 94;

        $this->initializeFaction("Castille");
        $this->Title = clienttranslate("Handyman and Husband");
        $this->Resolve = 5;
        $this->Combat = 0;
        $this->Finesse = 1;       
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Academic"),
            clienttranslate("Castille"),
        ];

        $this->Text = clienttranslate("<p>During pressures, while Aníbal is at a location with one or fewer Renown, he gains +2 [inf].</p><p>City Action: If Aníbal's location has no Renown • En garde him.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01094(),
        ];
    }

    public function getInfluencePressureValue(Theah $theah, string $locationName): int
    {
        $value = parent::getInfluencePressureValue($theah, $locationName);

        $location = $theah->getCityLocation($locationName);
        if ($location->Reknown <= 1)
        {
            $value += 2;
        }

        return $value;
    }
}