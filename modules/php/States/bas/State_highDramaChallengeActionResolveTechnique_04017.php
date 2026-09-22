<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaChallengeActionResolveTechnique_04017 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_04017,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaChallengeActionResolveTechnique_04017",

            description: clienttranslate('${actplayer} is choosing a card to discard.'),
            descriptionMyTurn: clienttranslate('Jägerarmbrust') . clienttranslate(': ${you} must choose a card to discard:'),
            transitions: [
                // WHY: Discard fires after Accept/Intervene from GENERATE_THREAT_EVENTS —
                // return there so threat resolution / duel start can finish (04033 returns
                // to RESOLVE_TECHNIQUE_EVENTS because its choice is pre-Accept).
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
