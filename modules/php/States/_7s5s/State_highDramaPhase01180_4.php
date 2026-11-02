<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01180_4 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01180_4,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01180_4",

            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('${you} must choose a Performer to Equip Artifact:'),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_01180,
                "performerChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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

    #[PossibleAction]
    public function actBack(): void
    {
        $this->game->actBack();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named back/performerChosen — do not invent a performer; actBack().
        // Old ZombieTrait wrongly fell into Crystal Eye opponent picker.
        $this->game->actBack();
    }
}
