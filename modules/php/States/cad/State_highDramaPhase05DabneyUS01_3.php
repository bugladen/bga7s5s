<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05DabneyUS01_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01_3,
            type: StateType::GAME,
            name: "highDramaPhase05DabneyUS01_3",

            // WHY: Post-move bridge — CardMoved reactions already drained. stateFromAction
            // either opens the challenge target picker or ActionResolved (L→R fail-forward).
            transitions: [
                "hasTargets" => States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01_4,
                "noTargets" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
