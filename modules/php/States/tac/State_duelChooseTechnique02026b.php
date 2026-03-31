<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique02026b extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_02026b,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_02026b",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Croc de Lion') . clienttranslate(': ${you} must choose an engaged adversary attachment to destroy: '),
            transitions: [
                "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
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
