<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Dandyb extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05DANDYB,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Dandyb",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is paying 2 Wealth.',
            descriptionMyTurn: 'Claude de la Roche: ${you} must pay 2 Wealth:',
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
        $ids = $this->chooseTwoWealthPayment($hand);
        if ($ids === null)
        {
            $resolved = EventFactory::createActionResolvedEvent($playerId);
            $this->game->theah->queueEvent($resolved);
            $this->game->gamestate->nextState();
            return;
        }

        $this->game->actFromCardWithIds(json_encode($ids));
    }

    /**
     * Prefer one Wealth card (exactly 2). Otherwise the first two plain cards.
     *
     * @param array<int, array<string, mixed>> $hand
     * @return list<int>|null
     */
    private function chooseTwoWealthPayment(array $hand): ?array
    {
        $plain = [];
        foreach ($hand as $row)
        {
            $card = $this->game->getCardObjectFromDb((int) $row['id']);
            if ($card === null)
            {
                continue;
            }

            if ($card->hasTrait('Wealth'))
            {
                return [(int) $card->Id];
            }

            $plain[] = (int) $card->Id;
            if (count($plain) === 2)
            {
                return $plain;
            }
        }

        return null;
    }
}
