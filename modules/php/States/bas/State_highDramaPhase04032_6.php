<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04032_6 extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04032_6,
            type: StateType::GAME,
            name: "highDramaPhase04032_6",

            transitions: [
                "" => States::HIGH_DRAMA_PLAYER_TURN_04032_5,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: 01192_2 shape — after Giacinto views chooseList, set active player to the
        // hand owner before the discard picker.
        $this->game->theah->buildCity();

        $targetId = (int)$this->game->globals->get(Game::CHOSEN_TARGET);
        $target = $this->game->theah->getCharacterById($targetId);
        if ($target !== null)
        {
            $this->game->gamestate->changeActivePlayer($target->ControllerId);
        }

        $this->game->gamestate->nextState("");
    }
}
