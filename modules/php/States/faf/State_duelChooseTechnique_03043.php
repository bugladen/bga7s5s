<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_03043 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_03043,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            name: "duelChooseTechnique_03043",

            description: clienttranslate('El Gato\'s Mask') . clienttranslate(': Your opponent(s) must acknowledge revealed cards.'),
            descriptionMyTurn: clienttranslate('El Gato\'s Mask') . clienttranslate(': ${you} must acknowledge revealed cards:'),
            transitions: [
                "multipleOk" => States::DUEL_CHOOSE_TECHNIQUE_03043_2,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    function onEnteringState(int $activePlayerId): void
    {
        // WHY: Mirror SETUP_TABLE_01006_2 — initiator already saw the reveal; others must Ok
        // unless they have Auto-Acknowledge Card Reveals (pref 110).
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
