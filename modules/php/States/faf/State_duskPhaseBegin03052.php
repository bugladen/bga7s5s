<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duskPhaseBegin03052 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUSK_PHASE_BEGIN_03052,
            type: StateType::ACTIVE_PLAYER,
            name: "duskPhaseBegin03052",

            description: clienttranslate('${actplayer} is looking at the City Deck.'),
            descriptionMyTurn: clienttranslate('Yevgeni') . clienttranslate(': ${you} must choose one card to sink: '),
            transitions: [
                "reorder" => States::DUSK_PHASE_BEGIN_03052_2,
                "done" => States::DUSK_PHASE_BEGIN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: Looked-at City Deck cards must stay private (Penya duskPhaseBegin01177_2).
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("done");
    }
}
