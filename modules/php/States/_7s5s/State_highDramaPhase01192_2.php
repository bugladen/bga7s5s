<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

/**
 * @@@STALE_STATE_SHIM_01192@@@ delete this whole file when no live tables can hold 4011922.
 * Restores CURRENT_PLAYER then enters live 01192 (not legacy _3).
 */
class State_highDramaPhase01192_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01192_2,
            type: StateType::GAME,
            name: "highDramaPhase01192_2",

            transitions: [
                "" => States::HIGH_DRAMA_PLAYER_TURN_01192,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        $this->game->stSetCurrentPlayer();
    }
}
