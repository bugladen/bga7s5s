<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelEndOfRound_01096 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_END_OF_ROUND_01096,
            type: StateType::ACTIVE_PLAYER,
            name: "duelEndOfRound_01096",

            // optional
            description: clienttranslate('${actplayer} is choosing End of Round options for Ratón.'),
            descriptionMyTurn: clienttranslate('Ratón') . clienttranslate(': ${you} must choose an Equipped Enemy Card to Steal: '),
            transitions: [
                "" => States::DUEL_END_OF_ROUND_EVENTS,
            ],
        );
    }
    
    public function getArgs(): array
    {
        return $this->game->argsForState();
    } 

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }

}

