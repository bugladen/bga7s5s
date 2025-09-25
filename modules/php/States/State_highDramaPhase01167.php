<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01167 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01167,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01167",

            // optional
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Liberating Goods') . clienttranslate(': ${you} must choose an opponent to view their discard pile: '),
            transitions: [
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "opponentChosen" => States::HIGH_DRAMA_PLAYER_TURN_01167_2
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
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->gamestate->nextState("zombie");
    }

}