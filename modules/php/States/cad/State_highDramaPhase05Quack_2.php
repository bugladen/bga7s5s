<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Quack_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05QUACK_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Quack_2",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} must discard a card.',
            descriptionMyTurn: 'Takama Siad: ${you} must discard a card from your hand:',
            // WHY: sole "" exit — heal already flushed via EVENTS; discard is mandatory.
            transitions: [
                "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
        // WHY: Heal already resolved; discard must complete so ActionResolved still fires.
        $hand = $this->game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        if (count($hand) > 0)
        {
            $first = array_values($hand)[0];
            $this->game->actFromCardWithId((string)$first->Id);
        }
        else
        {
            $this->game->gamestate->nextState("");
        }
    }
}
