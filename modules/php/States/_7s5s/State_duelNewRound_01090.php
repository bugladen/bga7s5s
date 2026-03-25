<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelNewRound_01090 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_NEW_ROUND_01090,
            type: StateType::ACTIVE_PLAYER,
            name: "duelNewRound_01090",

            description: clienttranslate('Lorenzo de Zepeda') . clienttranslate(': ${actplayer} is choosing Options.'),
            descriptionMyTurn: clienttranslate('Lorenzo de Zepeda') . clienttranslate(': ${you} must choose: Discard a card to play the revealed card as your Combat Card, or take a Wound: '),
            transitions: [
                "" => States::DUEL_NEW_ROUND_EVENTS,
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

