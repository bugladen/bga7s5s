<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_04001_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_04001_2,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_04001_2",

            description: clienttranslate('${actplayer} is reordering Faction Deck cards.'),
            descriptionMyTurn: clienttranslate('Benci Bommarito') . clienttranslate(': ${you} must reorder the remaining cards:'),
            transitions: [
                "cardsSorted" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
                "done" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: Remaining looked-at cards stay private (duskPhaseBegin03052_2).
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("done");
    }
}
