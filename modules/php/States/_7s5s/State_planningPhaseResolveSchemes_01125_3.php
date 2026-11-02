<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01125_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_3,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01125_3",

            description: clienttranslate('The Boar\'s Guile: ${actplayer} must choose an adjacent City Location to move the Renown TO.'),
            descriptionMyTurn: clienttranslate('The Boar\'s Guile: ${you} must choose an adjacent City Location to move the Renown TO:'),
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4,
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
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        // Skips the adjacent move; Renown already removed from source in _2.
        $this->game->gamestate->nextState();
    }
}
