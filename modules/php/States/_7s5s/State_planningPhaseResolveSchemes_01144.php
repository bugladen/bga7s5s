<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01144 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01144,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01144",

            description: clienttranslate('Filling The Ranks: ${actplayer} must choose a city location to place Renown onto.'),
            descriptionMyTurn: clienttranslate('Filling The Ranks: ${you} must choose a city location to place Renown onto:'),
            transitions: [
                "fewestReknown" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2,
                "notFewestReknown" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS, // Zombie mode will pass here
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

    public function zombie(int $playerId): void
    {
        // WHY: Named fewestReknown/notFewestReknown/pass — bare nextState() illegal.
        // Humans have no Pass UI (must place Renown). "pass" transition is zombie-only
        // escape via actPass("pass") (old ZombieTrait / machinestate comment).
        $this->game->actPass("pass");
    }
}
