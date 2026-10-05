<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_05Cooper extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_05COOPER,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_05Cooper",

            description: clienttranslate('${actplayer} is choosing a Red Hand to wound.'),
            // WHY: Short descriptionMyTurn without Title — CAD checklist prefers that
            // unless same-name disambiguation requires Title (Dabney/Valeri shape).
            descriptionMyTurn: clienttranslate('Don Vissenta Scarpa') . clienttranslate(': ${you} must choose a Red Hand to wound:'),
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
        // WHY: Mirror Technique_02006 — bare nextState on zombie (may skip wound cost).
        $this->game->gamestate->nextState();
    }
}
