<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;

/** In-hand Risk Action fixture (id becomes "{riskId}_GenericRiskAction" once placed). */
class GenericRiskAction extends RiskAction
{
    use RecordingActionTrait;

    public function __construct(bool $requiresPerformer = false)
    {
        parent::__construct();
        $this->Name = 'Test Risk Action';
        $this->RequiresPerformerSelected = $requiresPerformer;
    }
}
