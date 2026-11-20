<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes05Rum_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM_2,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_05Rum_2",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'X Marks the Spot: ${actplayer} must choose a different location to place a Renown onto.',
            descriptionMyTurn: 'X Marks the Spot: ${you} must choose a different location to place a Renown onto:',
            transitions: [
                "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM,
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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
    public function actBack(): void
    {
        $this->game->gamestate->nextState("back");
    }

    #[PossibleAction]
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
