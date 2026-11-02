<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01076 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01076,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01076",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Blood Mark') . clienttranslate(': ${you} must choose a City location to move Performer: '),
            transitions: [
                // WHY: Added on GameState conversion. Old def had only locationChosen;
                // ZombieTrait called actBack() but there was no "back" (UI never shows
                // Back here — Risk already paid). Without a zombie escape, nextState
                // ("locationChosen") would enter _2 with no location then actBack-loop.
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_01076_2,
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
    public function actBack(): void
    {
        $this->game->actBack();
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
