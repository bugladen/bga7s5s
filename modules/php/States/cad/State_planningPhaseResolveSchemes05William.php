<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes05William extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_05William",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'Syrneth Secrets: ${actplayer} must choose an Academic to move.',
            descriptionMyTurn: 'Syrneth Secrets: ${you} must choose an Academic to move:',
            transitions: [
                "academicChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_05WILLIAM_2,
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
