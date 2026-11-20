<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase05RedTape_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_2,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase05RedTape_2",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} must reveal four cards from hand.',
            descriptionMyTurn: 'Customs Inspection: ${you} must choose four cards from your hand to reveal:',
            transitions: [
                "cardsRevealed" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
        $hand = array_values($this->game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId));
        $pick = array_slice($hand, 0, 4);
        if (count($pick) === 4)
        {
            $ids = array_map(fn($card) => $card->Id, $pick);
            $this->game->actFromCardWithIds(json_encode($ids));
            return;
        }

        $this->game->gamestate->nextState("zombie");
    }
}
