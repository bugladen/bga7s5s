<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

/**
 * GenericCharacter that can carry Actions / Techniques / Maneuvers.
 *
 * Add abilities BEFORE Game::place(): Card::setId rewrites every ability's owner id, which is
 * what turns "GenericCharacterAction" into "{cardId}_GenericCharacterAction".
 */
class AbilityCharacter extends GenericCharacter implements IHasActions, IHasTechniques, IHasManeuvers
{
    use ActionTrait;
    use TechniqueTrait;
    use ManeuverTrait;
}
