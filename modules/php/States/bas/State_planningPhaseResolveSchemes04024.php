<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes04024 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_04024,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_04024",

            description: clienttranslate('Diplomatic Envoy') . clienttranslate(': ${actplayer} must choose a Diplomat to move to The City Forum.'),
            descriptionMyTurn: clienttranslate('Diplomatic Envoy') . clienttranslate(': ${you} must choose a Diplomat to move to The City Forum:'),
            // WHY: Named success — nextState("") with "zombie" present throws
            // "More than one possible transition" (same trap as _04004 / _03042).
            transitions: [
                "diplomatChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
