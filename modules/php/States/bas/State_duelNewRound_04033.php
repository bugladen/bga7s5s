<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelNewRound_04033 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_NEW_ROUND_04033,
            type: StateType::ACTIVE_PLAYER,
            name: "duelNewRound_04033",

            description: clienttranslate('${actplayer} is choosing options.'),
            descriptionMyTurn: clienttranslate('Iago Carlos de Soldano') . clienttranslate(': ${you} may add a threat to Iago: '),
            transitions: [
                "" => States::DUEL_NEW_ROUND_EVENTS,
            ],
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsEmpty();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // Decline the optional threat (PendingThreatChoice already cleared on enter).
        $this->game->gamestate->nextState();
    }
}
