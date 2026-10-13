<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaChallengeActionResolveTechnique_04001_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_04001_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaChallengeActionResolveTechnique_04001_2",

            description: clienttranslate('${actplayer} is reordering Faction Deck cards.'),
            descriptionMyTurn: clienttranslate('Benci Bommarito') . clienttranslate(': ${you} must reorder the remaining cards:'),
            transitions: [
                // WHY: Same GENERATE_THREAT_EVENTS hub as sink "done" (01090 / 04017).
                "cardsSorted" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS,
                "done" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: Remaining looked-at cards stay private (duskPhaseBegin03052_2).
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("done");
    }
}
