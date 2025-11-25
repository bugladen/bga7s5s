<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_01135 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_01135,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_01135",

            // optional
            description: clienttranslate('${actplayer} is choosing options from Mireli\'s Revision.'),
            descriptionMyTurn: clienttranslate('Mireli\'s Revision') . clienttranslate(': ${you} must choose +2 Parry, or wound adversary and give them -2 Thrust next round: '),
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

