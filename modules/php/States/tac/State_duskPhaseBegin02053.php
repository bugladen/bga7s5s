<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duskPhaseBegin02053 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUSK_PHASE_BEGIN_02053,
            type: StateType::ACTIVE_PLAYER,
            name: "duskPhaseBegin02053",

            description: clienttranslate('Under Cover of the Night') . clienttranslate(': ${actplayer} may send a card from a discard pile to The Locker.'),
            descriptionMyTurn: clienttranslate('Under Cover of the Night') . clienttranslate(': ${you} may select a card to send to The Locker: '),
            transitions: [
                "" => States::DUSK_PHASE_BEGIN_EVENTS,
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
    public function actFromCardWithId(int $id): void
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
