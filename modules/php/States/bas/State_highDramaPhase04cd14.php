<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd14;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase04cd14 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_04CD14,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase04cd14",

            description: clienttranslate('Millstone Rhud') . clienttranslate(': ${actplayer} must choose an opposing character.'),
            descriptionMyTurn: clienttranslate('Millstone Rhud') . clienttranslate(': ${you} must choose an opposing character to wound:'),
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
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // Forced: auto-pick the first eligible opposing character.
        $game = $this->game;
        $sourceId = (int)$game->globals->get(Game::TRANSITION_SOURCE_ID);
        $card = $game->theah->getCardById($sourceId);

        if ($card instanceof _04cd14)
        {
            $opposing = $card->getEligibleOpposingCharacters($game);
            if (count($opposing) > 0)
            {
                $first = $opposing[0];
                $card->actFromCardWithId(
                    $game,
                    States::HIGH_DRAMA_PLAYER_TURN_04CD14,
                    "highDramaPhase04cd14",
                    "",
                    $first->Id
                );
                return;
            }
        }

        $game->gamestate->nextState();
    }
}
