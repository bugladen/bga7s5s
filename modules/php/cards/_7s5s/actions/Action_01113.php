<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;

class Action_01113 extends RiskCityAction
{

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Take Control of Attachment in Opponent's Discard Pile");
        $this->RequiresPerformerSelected = true;
    }
}

    