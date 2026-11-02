<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01143 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01143,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01143",

            description: clienttranslate('Contempt and Hatred') . clienttranslate(': ${actplayer} may choose a city location to place Renown onto.'),
            descriptionMyTurn: clienttranslate('Contempt and Hatred') . clienttranslate(': ${you} may choose a city location to place Renown onto:'),
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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
    public function actPass(): void
    {
        $this->game->actPass();
    }

    #[PossibleAction]
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Sole "" transition — actPass() (default "") matches old ZombieTrait.
        $this->game->actPass();
    }
}
