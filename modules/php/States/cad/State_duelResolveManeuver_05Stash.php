<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelResolveManeuver_05Stash extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_RESOLVE_MANEUVER_05STASH,
            type: StateType::ACTIVE_PLAYER,
            name: "duelResolveManeuver_05Stash",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is choosing whether to discard an Attachment.',
            descriptionMyTurn: 'Extra Rations: ${you} may discard an Attachment equipped to your participant, or Decline:',
            transitions: [
                "" => States::DUEL_RESOLVE_MANEUVER_EVENTS,
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
        // WHY: Sole "" transition — bare nextState() (empty-transition rule).
        // Decline (id 0) so the engage stick still fires for the Maneuver owner.
        $this->game->actFromCardWithId('0');
    }
}
