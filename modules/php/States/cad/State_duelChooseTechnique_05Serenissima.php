<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_05Serenissima extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_05SERENISSIMA,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_05Serenissima",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is choosing a card to discard.',
            descriptionMyTurn: 'Patricia Moustakas: ${you} must discard a card:',
            transitions: [
                "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
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
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Cost-bearing Technique — prefer discarding first hand card over skipping.
        $hand = $this->game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        if (count($hand) > 0)
        {
            $card = reset($hand);
            $this->game->actFromCardWithId((string)$card->Id);
            return;
        }

        $this->game->gamestate->nextState();
    }
}
