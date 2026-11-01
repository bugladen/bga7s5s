<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01035 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01035,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "highDramaPhase01035",

            description: clienttranslate('Your opponent(s) must acknowledge revealed cards.'),
            descriptionMyTurn: clienttranslate('Kaspar') . clienttranslate(': ${you} must acknowledge revealed cards:'),
            transitions: [
                "multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_01035_2,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: Kaspar/Otto reveal to everyone including initiator — full acknowledge.
        $this->game->stMultiPlayerInitCardRevealAcknowledge();
    }

    public function getArgs(): array
    {
        return $this->game->argsForState();
    }

    #[PossibleAction]
    public function actMultipleOk(): void
    {
        $this->game->actMultipleOk();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    }
}
