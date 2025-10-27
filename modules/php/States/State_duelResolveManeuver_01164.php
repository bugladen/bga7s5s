<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_01164 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_01164,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_01164",

            description: clienttranslate('${actplayer} is choosing their Duel Action options.'),
            descriptionMyTurn: clienttranslate('Hidden Corridors') . clienttranslate(': ${you} must choose a location to move to:'),
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
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }

}