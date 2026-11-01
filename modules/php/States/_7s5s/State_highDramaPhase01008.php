<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01008 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01008,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "highDramaPhase01008",

            description: clienttranslate('Cesca Del Rosso') . clienttranslate(': Your opponent(s) must acknowlege revealed card.'),
            descriptionMyTurn: clienttranslate('Cesca Del Rosso') . clienttranslate(': ${you} must must acknowlege revealed card:'),
            transitions: [
                "multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_01008_2,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: Same as SETUP_TABLE_01006_2 — initiator saw the reveal; others acknowledge.
        $this->game->stMultiPlayerInitCardRevealAcknowledgeSansInitiatingPlayer();
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
