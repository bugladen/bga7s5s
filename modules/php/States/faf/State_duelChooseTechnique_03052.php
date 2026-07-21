<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_03052 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_03052,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_03052",

            description: clienttranslate('${actplayer} is looking at their adversary\'s hand.'),
            descriptionMyTurn: clienttranslate('Yevgeni') . clienttranslate(': ${you} may look at your adversary\'s hand: '),
            transitions: [
                "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        // WHY: "Look at" is private — adversary hand must not leak via public args (unlike Reveal 03043).
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
