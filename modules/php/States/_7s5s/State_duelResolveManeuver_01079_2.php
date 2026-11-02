<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_01079_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_01079_2,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_01079_2",

            description: clienttranslate('${actplayer} is choosing to destroy their weapon or take a wound.'),
            descriptionMyTurn: clienttranslate('Disarm') . clienttranslate(': ${you} must choose to destroy your weapon or take a wound:'),
            transitions: [
                "" => States::DUEL_RESOLVE_MANEUVER_EVENTS,
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
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        $this->game->gamestate->nextState();
    }
}
