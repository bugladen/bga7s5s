<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;

/** Plain Risk Reaction fixture (id becomes "{riskId}_GenericRiskReaction" once placed). */
class GenericRiskReaction extends RiskReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Test Risk Reaction';
    }
}
