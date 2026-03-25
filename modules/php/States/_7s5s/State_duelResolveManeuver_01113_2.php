<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_01113_2 extends GameState
{
    function __construct(
        protected Game $game,
    ) 
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_01113_2,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_01113_2",

            description: clienttranslate('${actplayer} is choosing their Robbery options.'),
            descriptionMyTurn: clienttranslate('Robbery') . clienttranslate(': ${you} must pay #{cost} Wealth for the chosen Attachment:'),
            transitions: [
                "" => States::DUEL_RESOLVE_MANEUVER_EVENTS,
            ],
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
        $this->game->gamestate->nextState();
    }

}