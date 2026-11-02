<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaBeginning_01144 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_BEGINNING_01144,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaBeginning_01144",

            description: clienttranslate('Filling The Ranks: ${actplayer} may choose a Mercenary from a City Location to recruit to their home'),
            descriptionMyTurn: clienttranslate('Filling The Ranks: ${you} may choose a Mercenary from a City Location to recruit to your home:'),
            transitions: [
                "mercenaryChosen" => States::HIGH_DRAMA_BEGINNING_01144_2,
                "pass" => States::HIGH_DRAMA_BEGINNING_EVENTS,
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

    #[PossibleAction]
    public function actPassWithPass(): void
    {
        $this->game->actPassWithPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named mercenaryChosen/pass only — actPass("pass") (old ZombieTrait).
        $this->game->actPass("pass");
    }
}
