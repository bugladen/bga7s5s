<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase03063 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_03063,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase03063",

            description: clienttranslate('Smuggling Run') . clienttranslate(': ${actplayer} must choose a Renown or available attachment to move.'),
            descriptionMyTurn: clienttranslate('Smuggling Run') . clienttranslate(': ${you} must choose a Renown or an available attachment to move from your performer\'s location:'),
            transitions: [
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "thingChosen" => States::HIGH_DRAMA_PLAYER_TURN_03063_2,
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
