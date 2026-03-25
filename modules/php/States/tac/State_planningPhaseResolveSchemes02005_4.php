<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes02005_4 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_4,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_02005_4",

            // optional
            description: clienttranslate('Decipher the Strands') . clienttranslate(': ${actplayer} is choosing options to resolve.'),
            descriptionMyTurn: clienttranslate('Decipher the Strands') . clienttranslate(': ${you} may choose any number of cards to sink: '),
            transitions: [
                "allSunk" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "cardsChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_5,
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
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("allSunk");
    }

}
