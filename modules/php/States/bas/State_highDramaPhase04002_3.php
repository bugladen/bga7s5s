<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04002_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04002_3,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04002_3",

            description: clienttranslate('${actplayer} is choosing options for Danilo Danini.'),
            descriptionMyTurn: clienttranslate('Danilo Danini') . clienttranslate(': ${you} must wound the intervening character or draw a card:'),
            transitions: [
                // WHY: Return to GENERATE_THREAT_EVENTS so the remaining challenge queue finishes.
                "done" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS,
                "zombie" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS,
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
        // WHY: Prefer Draw for zombies — avoids hanging if intervener already left play.
        $this->game->actFromCardWithId(1);
    }
}
