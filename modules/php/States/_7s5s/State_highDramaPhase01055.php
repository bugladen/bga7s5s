<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01055 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01055,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01055",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Last Word') . clienttranslate(': ${you} must choose a character to move: '),
            transitions: [
                // WHY: Added on GameState conversion. Old def had only characterChosen;
                // ZombieTrait called actBack() but there was no "back" (UI never shows
                // Back here — Risk already paid). Without a zombie escape, nextState
                // ("characterChosen") would enter _2 with no target then actBack-loop.
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_01055_2,
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
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("zombie");
    }
}
