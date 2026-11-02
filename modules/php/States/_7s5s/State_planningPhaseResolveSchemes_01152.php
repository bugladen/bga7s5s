<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01152 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01152,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01152",

            description: clienttranslate('Until Morale Improves') . clienttranslate(': ${actplayer} may choose a City Location to place a Renown onto.'),
            descriptionMyTurn: clienttranslate('Until Morale Improves') . clienttranslate(': ${you} may choose a City Location to place a Renown onto: '),
            transitions: [
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2,
                "reknownPlaced" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named pass/reknownPlaced only. actPass("pass") skips card notify and
        // advances to _2 (old ZombieTrait).
        $this->game->actPass("pass");
    }
}
