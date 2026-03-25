<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01095 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01095,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "highDramaPhase01095",

            description: clienttranslate('Patricia Moustakas') . clienttranslate(': Your opponent(s) must discard a card.'),
            descriptionMyTurn: clienttranslate('Patricia Moustakas') . clienttranslate(': ${you} must choose a card to discard:'),
            transitions: [
                "multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
        );
    }
    
    function onEnteringState(int $activePlayerId) 
    {
        $this->game->stMultiPlayerInitSansInitiatingPlayer();
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
        $this->game->actMultipleOk();
    }

}

