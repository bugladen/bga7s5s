<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_01090 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_01090,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "duelChooseTechnique_01090",

            // optional
            description: clienttranslate('Lorenzo de Zepeda') . clienttranslate(': Your opponent(s) must acknowlege revealed card.'),
            descriptionMyTurn: clienttranslate('Lorenzo de Zepeda') . clienttranslate(': ${you} must must acknowlege revealed card:'),
            transitions: [
                "multipleOk" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }
    
    function onEnteringState(int $activePlayerId) 
    {
        $this->game->stMultiPlayerInitCardRevealAcknowledge();
    }   
    
    public function getArgs(): array
    {
        return $this->game->argsForState();
    } 

    #[PossibleAction]
    public function actMultipleOk(): void
    {
        $playerId = $this->game->getCurrentPlayerId();
        $this->game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    }

}

