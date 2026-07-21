<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duskPhaseBegin03052_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUSK_PHASE_BEGIN_03052_2,
            type: StateType::ACTIVE_PLAYER,
            name: "duskPhaseBegin03052_2",

            description: clienttranslate('${actplayer} is ordering City Deck cards.'),
            descriptionMyTurn: clienttranslate('Yevgeni') . clienttranslate(': ${you} must choose the order to replace the remaining City Deck cards: '),
            transitions: [
                "" => States::DUSK_PHASE_BEGIN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: Remaining looked-at cards stay private until sunk/reordered publicly only as sink notify.
        return $this->game->argsForStatePrivate();
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
