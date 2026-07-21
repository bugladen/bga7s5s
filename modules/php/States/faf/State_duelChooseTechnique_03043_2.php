<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_03043_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_03043_2,
            type: StateType::GAME,
            name: "duelChooseTechnique_03043_2",

            transitions: [
                "done" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
                "discard" => States::DUEL_CHOOSE_TECHNIQUE_03043_3,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: Kaspar 01035_2 pattern — branch after multi-ack via technique stateFromTechnique.
        $this->game->stFromCard();
    }
}
