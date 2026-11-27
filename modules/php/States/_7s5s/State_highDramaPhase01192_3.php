<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

/**
 * @@@STALE_STATE_SHIM_01192@@@ delete this whole file when no live tables can hold 4011923.
 * Same choose-Risk UX as live 01192 so stuck tables can finish.
 */
class State_highDramaPhase01192_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01192_3,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01192_3",

            description: clienttranslate('${actplayer} may choose a Risk to put into their Faction Hand.'),
            descriptionMyTurn: clienttranslate('${you} may choose a Risk to put into your Faction Hand:'),
            transitions: [
                "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
        // WHY: Named cardChosen/pass — decline via pass.
        $this->game->actFromCardPass();
    }
}
