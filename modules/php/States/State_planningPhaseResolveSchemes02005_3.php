<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes02005_3 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_3,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_02005_3",

            // optional
            description: clienttranslate('Decipher the Strands') . clienttranslate(': ${actplayer} must choose an opponent to manipulate the top cards of their deck.'),
            descriptionMyTurn: clienttranslate('Decipher the Strands') . clienttranslate(': ${you} must choose an opponent to manipulate the top cards of their deck: '),
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_4,
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
        $this->game->gamestate->nextState();
    }

}
