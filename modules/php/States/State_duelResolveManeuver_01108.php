<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_01108 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_01108,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_01108",

            description: clienttranslate('${actplayer} is choosing a card to discard.'),
            descriptionMyTurn: clienttranslate('Life in the Canals') . clienttranslate(': ${you} must choose a card to discard:'),
            transitions: [
                "" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS,
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