<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes05William_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_2,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_05William_2",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'Syrneth Secrets: ${actplayer} must choose a City location for their Academic.',
            descriptionMyTurn: 'Syrneth Secrets: ${you} must choose a City location for your Academic:',
            // WHY: Named success — nextState("") with "back" present throws
            // "More than one possible transition" (same trap as _04004).
            transitions: [
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM,
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
        $this->game->actBack();
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
