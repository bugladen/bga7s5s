<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04034 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04034,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04034",

            description: clienttranslate('Explosive Ultimatum') . clienttranslate(': ${actplayer} may lose control of ${location}, or decline and wound opposing characters there.'),
            descriptionMyTurn: clienttranslate('Explosive Ultimatum') . clienttranslate(': ${you} may lose control of ${location}. If you do not, all opposing characters there are wounded:'),
            transitions: [
                "choiceMade" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: Flatten location for description ${location} substitution; keep nested
        // args for OnEnteringState / button JS (args.args.args.*).
        $wrapped = $this->game->argsForState();
        $inner = $wrapped['args'] ?? [];
        $wrapped['location'] = $inner['location'] ?? '';
        return $wrapped;
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // Prefer lose control when legal; else decline (wounds).
        $args = $this->game->argsForState();
        $canLoseControl = $args['args']['canLoseControl'] ?? false;
        $this->game->actFromCardWithId($canLoseControl ? 1 : 2);
    }
}
