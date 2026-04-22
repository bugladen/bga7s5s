<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duskPhaseBegin02024 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUSK_PHASE_BEGIN_02024,
            type: StateType::ACTIVE_PLAYER,
            name: "duskPhaseBegin02024",

            // optional
            description: clienttranslate('Oath of Vengeance') . clienttranslate(': ${actplayer} is choosing a location to move Renown from this card.'),
            descriptionMyTurn: clienttranslate('Oath of Vengeance') . clienttranslate(': ${you} must choose a location to move Renown from this card: '),
            transitions: [
                "" => States::DUSK_PHASE_BEGIN_EVENTS,
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
