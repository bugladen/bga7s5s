<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Corncob_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Corncob_2",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            // WHY no Back: Weapon already chosen; take-control+pay path begins after host pick.
            description: '${actplayer} is choosing options to perform an Action.',
            descriptionMyTurn: 'Cobb Roy: ${you} must choose a character at this location to equip the Weapon:',
            transitions: [
                "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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

    public function zombie(int $playerId): void
    {
        $args = $this->game->argsForState();
        $ids = $args["args"]["ids"] ?? [];
        if (count($ids) > 0)
        {
            $this->game->actFromCardWithId((string)$ids[0]);
            return;
        }

        $this->game->gamestate->nextState("zombie");
    }
}
