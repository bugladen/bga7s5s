<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01072_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01072_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01072_2",

            description: clienttranslate('${actplayer} is choosing a card to Muster.'),
            descriptionMyTurn: clienttranslate('Réputation Méritée') . clienttranslate(': ${you} must choose a card to Muster: '),
            transitions: [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_01072,
                "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
        // WHY: Named back/cardChosen only — bare nextState() invalid. Escape via Back.
        // Old ZombieTrait put this under nextState() (mislabel "city card") and had a
        // phantom highDramaPhase01072_3 actBack case for the muster step.
        $this->game->actBack();
    }
}
