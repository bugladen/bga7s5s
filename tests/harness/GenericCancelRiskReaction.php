<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;

/** Risk Reaction that cancels something (ICancelReaction): its events are STACKED, not queued. */
class GenericCancelRiskReaction extends RiskReaction implements ICancelReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Test Cancel Reaction';
    }
}
