<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04037_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04037_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04037_2",

            description: clienttranslate('Cats in Every Corner') . clienttranslate(': ${actplayer} may add a City Deck card to this location.'),
            descriptionMyTurn: clienttranslate('Cats in Every Corner') . clienttranslate(': ${you} may add one of these City Deck cards to this location, then sink the rest:'),
            // WHY: Named transitions — never pair "" with pass/zombie (BGA multi-transition pitfall).
            transitions: [
                "cardAdded" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
