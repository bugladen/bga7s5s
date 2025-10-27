<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseEnd_01098 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_END_01098,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseEnd_01098",

            // optional
            description: clienttranslate('The Cat\'s Embargo') . clienttranslate(': ${actplayer} must choose an opponent to reveal a card from hand.'),
            descriptionMyTurn: clienttranslate('The Cat\'s Embargo') . clienttranslate(': ${you} must choose an opponent to reveal a card from hand:'),
            transitions: [
                "" => States::PLANNING_PHASE_END_01098_2
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
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }

}