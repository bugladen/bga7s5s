<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes02035 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_02035,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_02035",

            description: clienttranslate('Castillian Caper') . clienttranslate(': ${actplayer} must choose location(s) with no Renown to place Renown onto (up to two).'),
            descriptionMyTurn: clienttranslate('Castillian Caper') . clienttranslate(': ${you} must choose location(s) with no Renown to place Renown onto (up to two): '),
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
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
        $this->game->gamestate->nextState();
    }

}
