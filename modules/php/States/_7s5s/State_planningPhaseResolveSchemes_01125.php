<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01125 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01125,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01125",

            description: clienttranslate('The Boar\'s Guile: ${actplayer} may choose a City Location to place a Renown onto.'),
            descriptionMyTurn: clienttranslate('The Boar\'s Guile: ${you} may choose a City Location to place a Renown onto: '),
            transitions: [
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_2,
                "reknownPlaced" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4,
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
        // WHY: Named pass/reknownPlaced only — bare nextState() invalid. Escape via
        // actPass("pass") (not actFromCardPass) so we skip card notify and still land on _2.
        $this->game->actPass("pass");
    }
}
