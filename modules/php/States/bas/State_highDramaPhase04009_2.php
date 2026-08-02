<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04009_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04009_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04009_2",

            description: clienttranslate('${actplayer} is choosing a character to issue a challenge.'),
            descriptionMyTurn: clienttranslate('Rattle the Rigging') . clienttranslate(': ${you} must choose one of your characters opposing the performer to issue a Combat challenge:'),
            transitions: [
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
