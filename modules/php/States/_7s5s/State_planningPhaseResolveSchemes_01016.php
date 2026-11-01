<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01016 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01016,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01016",

            description: clienttranslate('Plans Within Plans') . clienttranslate(': ${actplayer} must choose two city locations to place Renown onto.'),
            descriptionMyTurn: clienttranslate('Plans Within Plans') . clienttranslate(': ${you} must choose two city locations to place Renown onto:'),
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsEmpty();
    }

    #[PossibleAction]
    public function actCityLocationsForRenownSelected(string $locations): void
    {
        $this->game->actCityLocationsForRenownSelected($locations);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Sole "" transition — bare nextState() is valid here (same as 01098).
        $this->game->gamestate->nextState();
    }
}
