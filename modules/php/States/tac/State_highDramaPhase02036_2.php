<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase02036_2 extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_02036_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase02036_2",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Rumors of the Crimson Roger') . clienttranslate(': ${you} may discard a card to prevent your character from being moved Home, or pass to allow the move: '),
            transitions: [
                "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        $this->game->actFromCardPass();
    }
}
