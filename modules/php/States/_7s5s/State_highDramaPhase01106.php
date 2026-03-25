<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01106 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01106,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01106",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Improvising') . clienttranslate(': ${you} must choose an opponent to play a Risk from their Discard Pile: '),
            transitions: [
                "back" => States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_ACTION,
                "opponentChosen" => States::HIGH_DRAMA_PLAYER_TURN_01106_2,
            ],
            updateGameProgression: false,
        );
    }
    
    public function getArgs(): array
    {
        return $this->game->argsForState();
    } 

    #[PossibleAction]
    public function actBack(): void
    {
        $this->game->actBack();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->actBack();
    }

}