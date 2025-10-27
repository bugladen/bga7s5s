<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01164 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01164,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01164",

            description: clienttranslate('${actplayer} is choosing their High Drama Action options.'),
            descriptionMyTurn: clienttranslate('Hidden Corridors') . clienttranslate(': ${you} must choose a Non-Adjacent location to move to:'),
            transitions: [
                "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
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