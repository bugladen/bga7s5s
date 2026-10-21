<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;

/** In-play Character Action fixture (id becomes "{ownerId}_GenericCharacterAction" once placed). */
class GenericCharacterAction extends CharacterAction
{
    use RecordingActionTrait;

    public function __construct(bool $requiresPerformer = false)
    {
        parent::__construct();
        $this->Name = 'Test Character Action';
        $this->RequiresPerformerSelected = $requiresPerformer;
    }
}
