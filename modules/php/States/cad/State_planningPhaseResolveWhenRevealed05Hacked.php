<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Hacked;

class State_planningPhaseResolveWhenRevealed05Hacked extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_05HACKED,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveWhenRevealed_05Hacked",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: 'Stolen Schematics: ${actplayer} must choose an opponent\'s scheme to copy.',
            descriptionMyTurn: 'Stolen Schematics: ${you} must choose an opponent\'s scheme in play or in The Locker to copy:',
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_EVENTS,
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
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        $sourceId = (int) $this->game->globals->get(Game::TRANSITION_SOURCE_ID, 0);
        $scheme = $this->game->theah->getCardById($sourceId);
        if ($scheme instanceof _05Hacked)
        {
            $ids = $scheme->getEligibleOpponentSchemeIds($this->game->theah, $scheme->ControllerId);
            if (count($ids) > 0)
            {
                $scheme->actFromCardWithId(
                    $this->game,
                    States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_05HACKED,
                    $this->name,
                    '',
                    $ids[0]
                );
                return;
            }
        }

        $this->game->gamestate->nextState();
    }
}
