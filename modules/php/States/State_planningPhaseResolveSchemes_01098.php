<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01098 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01098,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01098",

            // optional
            description: clienttranslate('The Cat\'s Embargo') . clienttranslate(': ${actplayer} must choose two city locations to place Renown onto: '),
            descriptionMyTurn: clienttranslate('The Cat\'s Embargo') . clienttranslate(': ${you} must choose two city locations to place Renown onto: '),
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
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