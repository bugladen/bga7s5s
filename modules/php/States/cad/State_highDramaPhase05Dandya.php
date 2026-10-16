<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Dandya extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05DANDYA,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Dandya",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is paying 1 Wealth.',
            descriptionMyTurn: 'Claude de la Roche: ${you} must discard a card to pay 1 Wealth:',
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
    public function actFromCardWithIds(string $ids): void
    {
        $this->game->actFromCardWithIds($ids);
    }

    public function zombie(int $playerId): void
    {
        $hand = array_values($this->game->getGameDeckObject()->getCardsInLocation(Game::LOCATION_HAND, $playerId));
        if (count($hand) === 0)
        {
            $resolved = EventFactory::createActionResolvedEvent($playerId);
            $this->game->theah->queueEvent($resolved);
            $this->game->gamestate->nextState();
            return;
        }

        $this->game->actFromCardWithIds(json_encode([(int) $hand[0]['id']]));
    }
}
