<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelEndOfRound_03022 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_END_OF_ROUND_03022,
            type: StateType::ACTIVE_PLAYER,
            name: "duelEndOfRound_03022",

            description: clienttranslate('${actplayer} is choosing End of Round options for Overzealous.'),
            descriptionMyTurn: clienttranslate('Overzealous') . clienttranslate(': ${you} must choose a character at this location to En Garde:'),
            transitions: [
                "" => States::DUEL_END_OF_ROUND_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
