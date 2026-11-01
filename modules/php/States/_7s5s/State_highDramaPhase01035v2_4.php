<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01035v2_4 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01035V2_4,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01035v2_4",

            description: clienttranslate('${actplayer} is choosing whether to parley with the revealed mercenary.'),
            descriptionMyTurn: clienttranslate('Kaspar') . clienttranslate(': ${you} must choose whether to parley with the revealed mercenary:'),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_01035V2_3,
                "parleyChosen" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_MERCENARY_EVENTS,
                "noParley" => States::HIGH_DRAMA_RECRUIT_ACTION_PAY_FOR_MERCENARY,
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
        // WHY: No Parley. Bare nextState("pass") is not a transition; Parley would open
        // City Reaction the zombie should not take. id 0 => noParley (same as old ZombieTrait).
        $this->game->actFromCardWithId(0);
    }
}
