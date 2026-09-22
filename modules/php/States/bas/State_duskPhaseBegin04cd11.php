<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\bas;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd11;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duskPhaseBegin04cd11 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUSK_PHASE_BEGIN_04CD11,
            type: StateType::ACTIVE_PLAYER,
            name: "duskPhaseBegin04cd11",

            description: clienttranslate('Let Bygones Be Bygones') . clienttranslate(': ${actplayer} must choose a character.'),
            descriptionMyTurn: clienttranslate('Let Bygones Be Bygones') . clienttranslate(': ${you} must choose one of your characters here:'),
            transitions: [
                "" => States::DUSK_PHASE_BEGIN_EVENTS,
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
        // Forced: auto-pick the first eligible character.
        $game = $this->game;
        $sourceId = (int)$game->globals->get(Game::TRANSITION_SOURCE_ID);
        $card = $game->theah->getCardById($sourceId);

        if ($card instanceof _04cd11)
        {
            $characters = $card->getEligibleCharactersForPlayer($game, $playerId);
            if (count($characters) > 0)
            {
                $first = $characters[0];
                $card->actFromCardWithId(
                    $game,
                    States::DUSK_PHASE_BEGIN_04CD11,
                    "duskPhaseBegin04cd11",
                    "",
                    $first->Id
                );
                return;
            }
        }

        $game->gamestate->nextState();
    }
}
