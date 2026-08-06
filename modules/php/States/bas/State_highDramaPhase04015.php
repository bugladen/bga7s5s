<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04015 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04015,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04015",

            description: clienttranslate('Through Thick and Thin') . clienttranslate(': ${actplayer} must choose an uncontrolled City location.'),
            descriptionMyTurn: clienttranslate('Through Thick and Thin') . clienttranslate(': ${you} must choose an uncontrolled City location:'),
            // WHY: Named success — avoid nextState("") when sibling transitions exist.
            transitions: [
                "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
