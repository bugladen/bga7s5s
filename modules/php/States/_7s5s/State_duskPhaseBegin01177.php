<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duskPhaseBegin01177 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUSK_PHASE_BEGIN_01177,
            type: StateType::ACTIVE_PLAYER,
            name: "duskPhaseBegin01177",

            description: clienttranslate('${actplayer} is choosing options for Penya.'),
            descriptionMyTurn: clienttranslate('${you} may choose a character to follow Penya:'),
            transitions: [
                "pickCards" => States::DUSK_PHASE_BEGIN_01177_2,
                "pass" => States::DUSK_PHASE_BEGIN_EVENTS,
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
    public function actPassWithPass(): void
    {
        $this->game->actPassWithPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named pickCards/pass — decline follow via pass (UI actPassWithPass).
        // Old ZombieTrait called actPass(); actPass("pass") matches the named transition.
        $this->game->actPass("pass");
    }
}
