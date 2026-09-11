<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes04034 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_04034,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_04034",

            description: clienttranslate('Explosive Ultimatum') . clienttranslate(': ${actplayer} must choose a City Location to place a Renown onto.'),
            descriptionMyTurn: clienttranslate('Explosive Ultimatum') . clienttranslate(': ${you} must choose a City Location to place a Renown onto:'),
            // WHY: Named success — "pass" sibling for move-instead path (01152 shape).
            transitions: [
                "renownPlaced" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_2,
                "zombie" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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
        $this->game->gamestate->nextState("zombie");
    }
}
