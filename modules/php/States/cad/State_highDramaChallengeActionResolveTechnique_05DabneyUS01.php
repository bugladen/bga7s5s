<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05DabneyUS01;

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

            description: clienttranslate('${actplayer} is choosing options to perform a Technique.'),
            // WHY: Challenge Thrust-only — Riposte needs duel Calculate; Lethal does nothing
            // useful here (challenge threat is already capped at the challenge stat).
            descriptionMyTurn: clienttranslate('Valeri Mikhailov') . clienttranslate(': ${you} must choose +1 Thrust: '),
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
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Challenge UI is Thrust-only — zombie must apply Thrust.
        $this->game->actFromCardWithId((string) Technique_05DabneyUS01::CHOICE_THRUST);
    }
}
