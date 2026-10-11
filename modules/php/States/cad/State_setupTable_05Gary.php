<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_setupTable_05Gary extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::SETUP_TABLE_05GARY,
            type: StateType::ACTIVE_PLAYER,
            name: "setupTable_05Gary",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'Rena Klingenhalter: ${actplayer} is choosing an attachment from their Faction Deck to reveal and equip at -1 cost.',
            descriptionMyTurn: 'Rena Klingenhalter: ${you} must choose an attachment from your Faction Deck to reveal and equip at -1 cost:',
            transitions: [
                "cardChosen" => States::SETUP_TABLE_05GARY_2,
                "pass" => States::SETUP_TABLE_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        $this->game->actFromCardPass();
    }
}
