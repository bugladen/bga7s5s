<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01205_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01205_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01205_2",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('${you} must choose a location to move to: '),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_01205,
                "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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

    #[PossibleAction]
    public function actBack(): void
    {
        $this->game->actBack();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named back/locationChosen — actBack(). Old ZombieTrait used
        // nextState("pass") which is not a legal transition here.
        $this->game->actBack();
    }
}
