<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaBeginning_01144_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_BEGINNING_01144_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaBeginning_01144_2",

            description: clienttranslate('Filling The Ranks: ${actplayer} must choose cards from your Faction Hand to pay for selected Mercenary'),
            descriptionMyTurn: clienttranslate('Filling The Ranks: ${you} must choose cards from your Faction Hand to pay #{cost} Wealth for selected Mercenary: '),
            transitions: [
                "" => States::HIGH_DRAMA_BEGINNING_EVENTS,
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
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        // Skips payment; matches old ZombieTrait (does not attempt to pay).
        $this->game->gamestate->nextState();
    }
}
