<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01008_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01008_3,
            type: StateType::GAME,
            name: "highDramaPhase01008_3",

            transitions: [
                "cardDrawn" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "choose" => States::HIGH_DRAMA_PLAYER_TURN_01008_4,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        $this->game->stFromCard();
    }
}
