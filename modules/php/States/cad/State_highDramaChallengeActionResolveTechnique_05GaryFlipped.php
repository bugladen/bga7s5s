<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaChallengeActionResolveTechnique_05GaryFlipped extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_05GARYFLIPPED,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaChallengeActionResolveTechnique_05GaryFlipped",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is choosing an attachment to destroy.',
            descriptionMyTurn: 'Rena Klingenhalter: ${you} must choose an attachment to destroy:',
            transitions: [
                // WHY: Back to resolve hub — destroy cost must finish before Accept.
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS,
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
        $this->game->gamestate->nextState();
    }
}
