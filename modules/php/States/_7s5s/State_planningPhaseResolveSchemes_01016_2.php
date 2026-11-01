<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes_01016_2 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_01016_2",

            description: clienttranslate('Plans Within Plans') . clienttranslate(': ${actplayer} may search their deck for a Red Hand Thug.'),
            descriptionMyTurn: clienttranslate('Plans Within Plans') . clienttranslate(': ${you} may choose a Red Hand Thug from Your Deck:'),
            transitions: [
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "cardChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_3,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForStatePrivate();
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named pass/cardChosen only — actPass() uses "" and breaks.
        // Pass throws while Red Hand Thugs remain in deck; pick first or pass.
        $args = $this->game->argsForStatePrivate();
        $thugs = $args['_private']['active']['args']['thugs'] ?? [];
        if (!empty($thugs)) {
            $this->game->actFromCardWithId((int)($thugs[0]['id'] ?? $thugs[0]['Id']));
        } else {
            $this->game->actFromCardPass();
        }
    }
}
