<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_04033 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_04033,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_04033",

            description: clienttranslate('${actplayer} is choosing options to perform a Technique.'),
            descriptionMyTurn: clienttranslate('Iago Carlos de Soldano') . clienttranslate(': ${you} must choose +1 Thrust or +1 Parry: '),
            transitions: [
                "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
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
        // UseThrust stays false → +1 Parry default (01013 zombie shape).
        $this->game->gamestate->nextState();
    }
}
