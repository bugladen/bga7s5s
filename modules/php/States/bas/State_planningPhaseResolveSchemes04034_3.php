<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes04034_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_3,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_04034_3",

            description: clienttranslate('Explosive Ultimatum') . clienttranslate(': ${actplayer} must choose an adjacent City Location to move the Renown TO.'),
            descriptionMyTurn: clienttranslate('Explosive Ultimatum') . clienttranslate(': ${you} must choose an adjacent City Location to move the Renown TO:'),
            // WHY: Named success — nextState("") with "back" present throws
            // "More than one possible transition".
            transitions: [
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_2,
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
