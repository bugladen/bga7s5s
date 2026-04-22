<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase02020_2 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_02020_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase02020_2",

            // optional
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Yield') . clienttranslate(': ${you} must choose a Melee Weapon or Eisenfaust attachment to engage: '),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_02020,
                "attachmentChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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

    #[PossibleAction]
    public function actBack(): void
    {
        $this->game->actBack();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("attachmentChosen");
    }

}
