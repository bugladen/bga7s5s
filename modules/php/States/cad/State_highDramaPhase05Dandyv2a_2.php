<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05Dandyv2a_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05Dandyv2a_2",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is choosing an adjacent City location to move Renown to.',
            descriptionMyTurn: 'Claude de la Roche: ${you} must choose an adjacent City location to move a Renown to:',
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
    public function actFromCardWithLocations(string $locations): void
    {
        $this->game->actFromCardWithLocations($locations);
    }

    public function zombie(int $playerId): void
    {
        $this->game->theah->buildCity();
        $sourceId = (int) $this->game->globals->get(Game::TRANSITION_SOURCE_ID);
        $claude = $this->game->theah->getCardById($sourceId);
        $current = $claude === null ? '' : $claude->Location;

        // Mirror Action_05Dandyv2a::getDestinations — adjacent City locations only.
        $locations = $current === ''
            ? []
            : $this->game->theah->getAdjacentCityLocations($current, $includeHome = false);

        if (count($locations) === 0)
        {
            $resolved = EventFactory::createActionResolvedEvent($playerId);
            $this->game->theah->queueEvent($resolved);
            $this->game->gamestate->nextState();
            return;
        }

        $this->game->actFromCardWithLocations(json_encode([$locations[0]]));
    }
}
