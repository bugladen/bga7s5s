<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaChallengeActionResolveTechnique_05DabneyUS01 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_05DABNEYUS01,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaChallengeActionResolveTechnique_05DabneyUS01",

            description: '${actplayer} is choosing options to perform a Technique.',
            // WHY: Technique is duel-only after Parry replaced Thrust (no challenge-useful
            // choice). State kept registered so a stale transition can Pass out.
            descriptionMyTurn: 'Valeri Mikhailov' . ': ${you} have no Technique options before the duel: ',
            transitions: [
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS,
            ],
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsEmpty();
    }

    #[PossibleAction]
    public function actPass(): void
    {
        $this->game->gamestate->nextState();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
