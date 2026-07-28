<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_04001 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_04001,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_04001",

            description: clienttranslate('${actplayer} is choosing cards to sink from their Faction Deck.'),
            descriptionMyTurn: clienttranslate('Benci Bommarito') . clienttranslate(': ${you} may sink one or both looked-at cards:'),
            transitions: [
                "reorder" => States::DUEL_CHOOSE_TECHNIQUE_04001_2,
                "done" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: "Look at" — deck card identities must not leak via public args (03052 shape).
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState("done");
    }
}
