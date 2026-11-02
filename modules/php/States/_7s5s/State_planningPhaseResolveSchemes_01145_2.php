<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01145_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_2,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01145_2",

            description: clienttranslate('Inspire Generosity: ${actplayer} is choosing options for Inspire Generosity.'),
            descriptionMyTurn: clienttranslate('Inspire Generosity: ${you} must choose a city location to move the Renown to:'),
            transitions: [
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_3,
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS, // Zombie mode will pass here
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
        // WHY: Named locationChosen/pass only. Humans have no pass UI here — "pass"
        // transition is zombie escape via actPass("pass") (old ZombieTrait / comment).
        $this->game->actPass("pass");
    }
}
