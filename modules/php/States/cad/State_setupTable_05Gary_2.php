<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_setupTable_05Gary_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::SETUP_TABLE_05GARY_2,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "setupTable_05Gary_2",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'Rena Klingenhalter: Your opponent(s) must acknowledge the revealed card.',
            descriptionMyTurn: 'Rena Klingenhalter: ${you} must acknowledge the revealed card:',
            transitions: [
                "multipleOk" => States::SETUP_TABLE_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: Mirror SETUP_TABLE_01006_2 — initiator already saw the reveal; others must Ok.
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
