<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\tac;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaChallengeActionResolveTechnique02026a extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_02026a,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaChallengeActionResolveTechnique_02026a",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Croc de Lion') . clienttranslate(': ${you} must choose an adversary attachment to engage: '),
            transitions: [
                // WHY: Chooser fires after Accept/Intervene from GENERATE_THREAT_EVENTS —
                // return there so threat resolution / duel start can finish (04017 shape).
                // Pre-Accept RESOLVE_TECHNIQUE_EVENTS would apply engage even on Refuse.
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS,
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
