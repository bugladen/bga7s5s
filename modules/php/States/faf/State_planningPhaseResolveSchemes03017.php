<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes03017 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_03017,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_03017",

            description: clienttranslate('Noble Sacrifice') . clienttranslate(': ${actplayer} must choose two different city locations to place Renown onto.'),
            descriptionMyTurn: clienttranslate('Noble Sacrifice') . clienttranslate(': ${you} must choose two different city locations to place Renown onto: '),
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
    public function actCityLocationsForReknownSelected(string $locations): void
    {
        $this->game->actCityLocationsForReknownSelected($locations);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
