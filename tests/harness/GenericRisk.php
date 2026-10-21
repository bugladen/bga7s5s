<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

/**
 * Risk fixture: a hand card with a Wealth cost that can carry Actions, Reactions and
 * Maneuvers (also doubles as a Combat card for the Dueling Line).
 *
 * Add abilities BEFORE Game::place() so their ids pick up the card id.
 */
class GenericRisk extends Risk implements IHasActions, IHasReactions, IHasManeuvers
{
    use ActionTrait;
    use ReactionTrait;
    use ManeuverTrait;

    public function __construct(string $name = 'Test Risk', int $wealthCost = 0)
    {
        parent::__construct();
        $this->Name = $name;
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->WealthCost = $wealthCost;
    }
}
