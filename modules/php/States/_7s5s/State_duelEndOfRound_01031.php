<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelEndOfRound_01031 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_END_OF_ROUND_01031,
            type: StateType::ACTIVE_PLAYER,
            name: "duelEndOfRound_01031",

            description: clienttranslate('${actplayer} is choosing their Duel End of Round options.'),
            descriptionMyTurn: clienttranslate('Rough \'em Up') . clienttranslate(': ${you} may choose a Thug to destroy:'),
            transitions: [
                "" => States::DUEL_END_OF_ROUND_EVENTS,
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
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        $this->game->gamestate->nextState();
    }
}
