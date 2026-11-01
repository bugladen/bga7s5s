<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01071 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01071,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01071",

            description: clienttranslate('Épée Sanglante') . clienttranslate(': ${actplayer} must choose a city location to place Renown onto.'),
            descriptionMyTurn: clienttranslate('Épée Sanglante') . clienttranslate(': ${you} must choose a city location to place Renown onto:'),
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsEmpty();
    }

    #[PossibleAction]
    public function actCityLocationsForRenownSelected(string $locations): void
    {
        $this->game->actCityLocationsForRenownSelected($locations);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        $this->game->gamestate->nextState();
    }
}
