<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Reaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class GameReaction extends Reaction
{
    public string $Id;
    public string $Name;

    public function __construct()
    {
        parent::__construct();
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate("Game") . " > " . $theah->game->translate("Reaction") . ": ";
    }
}