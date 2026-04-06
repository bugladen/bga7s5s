<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase02045 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_02045,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase02045",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Path to Poluchatel') . clienttranslate(': ${you} must choose a Dar Matushki or Poluchatel card from your deck:'),
            transitions: [
                "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
    public function actPass(): void
    {
        $this->game->actPass();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
