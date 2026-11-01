<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01060_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01060_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01060_2",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Stratege') . clienttranslate(': ${you} must choose up to two Performers to move: '),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_01060,
                "performersChosen" => States::HIGH_DRAMA_PLAYER_TURN_01060_3,
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
    public function actBack(): void
    {
        $this->game->actBack();
    }

    #[PossibleAction]
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named back/performersChosen only — bare nextState() invalid. Escape via Back.
        $this->game->actBack();
    }
}
