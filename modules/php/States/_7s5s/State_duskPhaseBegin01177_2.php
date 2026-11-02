<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duskPhaseBegin01177_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUSK_PHASE_BEGIN_01177_2,
            type: StateType::ACTIVE_PLAYER,
            name: "duskPhaseBegin01177_2",

            description: clienttranslate('${actplayer} is choosing options for Penya.'),
            descriptionMyTurn: clienttranslate('${you} must choose the order to replace the top 3 cards of the City Deck:'),
            transitions: [
                "" => States::DUSK_PHASE_BEGIN_EVENTS,
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
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    #[PossibleAction]
    public function actPass(): void
    {
        $this->game->actPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        // Old ZombieTrait used actPass(); either exits the same way.
        $this->game->gamestate->nextState();
    }
}
