<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseEnd_03041 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_END_03041,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseEnd_03041",

            description: clienttranslate('Proper Study') . clienttranslate(': ${actplayer} must discard cards equal to the number drawn.'),
            descriptionMyTurn: clienttranslate('Proper Study') . clienttranslate(': ${you} must discard cards equal to the number drawn:'),
            transitions: [
                "" => States::PLANNING_PHASE_END_EVENTS,
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
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
