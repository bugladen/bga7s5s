<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes04004_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_04004_2,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_04004_2",

            description: clienttranslate('Blood Money') . clienttranslate(': ${actplayer} must choose a City location for their Duelist.'),
            descriptionMyTurn: clienttranslate('Blood Money') . clienttranslate(': ${you} must choose a City location for your Duelist:'),
            // WHY: Named success — nextState("") with "back" present throws
            // "More than one possible transition" (same trap as _03042 / 03029_3).
            transitions: [
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_04004,
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
