<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

class BasicChallengeAction extends Action implements IAbilityThatTargetsCharacters
{
    public string $Id = 'BasicChallenge';

    public function __construct()
    {
        parent::__construct();
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCardById($performerId);

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target is not in the same location as your Performer.")];
        }

        return [true, ""];
    }
}
