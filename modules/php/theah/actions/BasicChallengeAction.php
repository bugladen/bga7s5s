<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class BasicChallengeAction extends Action implements IAbilityThatTargetsCharacters
{
    public string $Id = 'BasicChallenge';

    public function __construct()
    {
        parent::__construct();
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        //This is a basic challenge action, handled via the main logic in the game state
        return false;
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
