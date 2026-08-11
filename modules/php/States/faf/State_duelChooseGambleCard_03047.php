<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseGambleCard_03047 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_GAMBLE_CARD_03047,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseGambleCard_03047",

            description: clienttranslate('${actplayer} is choosing the adversary\'s combat card.'),
            descriptionMyTurn: clienttranslate('Proper Drama') . clienttranslate(': ${you} must choose the adversary\'s combat card from their revealed cards:'),
            transitions: [
                "noManeuver" => States::DUEL_CHOOSE_GAMBLE_CARD_EVENTS,
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
        $this->game->actFromCardWithId((int) $id);
    }

    public function zombie(int $playerId): void
    {
        $this->game->theah->buildCity();
        $args = $this->game->argsForState();
        $cards = $args['args']['cards'] ?? [];
        if (count($cards) > 0)
        {
            $this->game->actFromCardWithId((int) $cards[0]['id']);
            return;
        }

        $this->game->gamestate->nextState("noManeuver");
    }
}
