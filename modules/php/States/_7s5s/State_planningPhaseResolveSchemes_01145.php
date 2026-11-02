<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01145 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01145,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01145",

            description: clienttranslate('Inspire Generosity: ${actplayer} is choosing options for Inspire Generosity.'),
            descriptionMyTurn: clienttranslate('Inspire Generosity: ${you} must choose a city location to move a Renown from:'),
            transitions: [
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_2,
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_3,
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
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named locationChosen/pass only. actPass("pass") skips card notify and
        // lands on _3 (old ZombieTrait). actFromCardPass would also work but keep parity.
        $this->game->actPass("pass");
    }
}
