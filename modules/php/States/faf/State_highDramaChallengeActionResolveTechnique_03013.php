<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaChallengeActionResolveTechnique_03013 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaChallengeActionResolveTechnique_03013",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Daniella Dietrich (Witch, Hunter)') . clienttranslate(': Wound and Swap with a Hunter or Zealot: ${you} must choose a Hunter or Zealot:'),
            transitions: [
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
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
