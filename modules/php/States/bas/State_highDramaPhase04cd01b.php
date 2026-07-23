<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04cd01b extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04CD01B,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04cd01b",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Penya') . clienttranslate(': ${you} must choose an opponent to play a Risk from their Discard Pile: '),
            transitions: [
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "opponentChosen" => States::HIGH_DRAMA_PLAYER_TURN_04CD01B_2,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForState();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
