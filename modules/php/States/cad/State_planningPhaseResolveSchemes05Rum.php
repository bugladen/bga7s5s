<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes05Rum extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_05Rum",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'X Marks the Spot: ${actplayer} must choose a City location to place this card onto.',
            descriptionMyTurn: 'X Marks the Spot: ${you} must choose a City location to place this card onto:',
            transitions: [
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM_2,
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

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
