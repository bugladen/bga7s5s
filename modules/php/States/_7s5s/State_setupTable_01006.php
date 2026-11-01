<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_setupTable_01006 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::SETUP_TABLE_01006,
            type: StateType::ACTIVE_PLAYER,
            name: "setupTable_01006",

            description: clienttranslate('Don Contanzo Scarpa') . clienttranslate(': ${actplayer} is choosing a Red Hand Thug from their Faction Deck to reveal and place in their Hand.'),
            descriptionMyTurn: clienttranslate('Don Contanzo Scarpa') . clienttranslate(': ${you} must choose a Red Hand Thug from your Faction Deck to reveal and place in your Hand:'),
            transitions: [
                "cardChosen" => States::SETUP_TABLE_01006_2,
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
        // WHY: Named "pass" transition — actPass() would use "" and break. Mirror Gary setup.
        $this->game->actFromCardPass();
    }
}
