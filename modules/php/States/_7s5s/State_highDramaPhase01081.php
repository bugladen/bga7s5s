<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01081 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01081,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01081",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Gallant Deeds') . clienttranslate(': ${you} must choose an Opposing Character to Engage:'),
            transitions: [
                "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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

    #[PossibleAction]
    public function actPass(): void
    {
        $this->game->actPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        $this->game->gamestate->nextState();
    }
}
