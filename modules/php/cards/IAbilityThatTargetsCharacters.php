<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;

interface IAbilityThatTargetsCharacters 
{
    function isValidTargetForAbility(Game $game, Character $character): bool;
}