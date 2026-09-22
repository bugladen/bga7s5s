<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaChallengeActionResolveTechnique_04033 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_04033,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaChallengeActionResolveTechnique_04033",

            description: clienttranslate('${actplayer} is choosing options to perform a Technique.'),
            // WHY: Challenge only offers Thrust — Parry has no effect before duel Calculate.
            descriptionMyTurn: clienttranslate('Iago Carlos de Soldano') . clienttranslate(': ${you} must choose +1 Thrust: '),
            transitions: [
                // WHY: Back to resolve hub — choice must complete before Accept/GenerateThreat.
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS,
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
        // WHY: Challenge UI is Thrust-only — zombie must apply Thrust, not Parry default.
        $this->game->actFromCardWithId("1");
    }
}
