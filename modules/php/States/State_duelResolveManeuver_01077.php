<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_01077 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_01077,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "duelResolveManeuver_01077",

            // optional
            description: clienttranslate('Broken-Time') . clienttranslate(': Your opponent(s) must acknowlege revealed cards.'),
            descriptionMyTurn: clienttranslate('Broken-Time') . clienttranslate(': ${you} must must acknowlege revealed cards:'),
            transitions: [
                "multipleOk" => States::DUEL_RESOLVE_MANEUVER_01077_2,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForState();
    } 
    
    function onEnteringState(int $activePlayerId) {
        $this->game->gamestate->setAllPlayersMultiactive();
        $this->gamestate->setPlayerNonMultiactive($activePlayerId, 'multipleOk');
    }

    #[PossibleAction]
    public function actMultipleOk(): void{
        $playerId = $this->game->getCurrentPlayerId();
        $this->game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    }


    public function zombie(int $playerId): void
    {
        $this->game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');

    }

}