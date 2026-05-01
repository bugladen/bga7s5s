<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase03cd03_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_03CD03_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase03cd03_2",

            // optional
            description: clienttranslate('Chance Meeting') . clienttranslate(': ${actplayer} may Muster a character from their Approach Deck.'),
            descriptionMyTurn: clienttranslate('Chance Meeting') . clienttranslate(': ${you} may Muster a character from your Approach Deck:'),
            transitions: [
                "musterResolved" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
        $this->game->actFromCardWithId(0);
    }
}
