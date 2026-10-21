<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\actions\LocationAction;

/** City location Action fixture (once-per-player-per-day). Register with TestTheah::registerTheahAction. */
class GenericLocationAction extends LocationAction
{
    use RecordingActionTrait;

    public function __construct(Game $game, array $playersUsed = [])
    {
        parent::__construct($playersUsed, $game);
        $this->Id = 'GenericLocationAction';
        $this->Name = 'Test Location Action';
        $this->LocationName = Game::LOCATION_CITY_DOCKS;
        $this->globalVariableName = 'genericLocationActionUsed';
    }

    /** @return list<int> */
    public function playersUsed(): array
    {
        return $this->playersUsed;
    }
}
