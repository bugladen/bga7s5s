<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes03006 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_03006,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_03006",

            description: clienttranslate('Premonition') . clienttranslate(': ${actplayer} must choose two different city locations to place Renown onto.'),
            descriptionMyTurn: clienttranslate('Premonition') . clienttranslate(': ${you} must choose two different city locations to place Renown onto: '),
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
