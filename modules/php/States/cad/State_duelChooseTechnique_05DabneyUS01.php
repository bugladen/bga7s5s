<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\cad;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05DabneyUS01;

class State_duelChooseTechnique_05DabneyUS01 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_05DABNEYUS01,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_05DabneyUS01",

            // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
            description: '${actplayer} is choosing options to perform a Technique.',
            // WHY: ${technique_choices} swaps when combat card(s) have dashed Riposte.
            descriptionMyTurn: 'Valeri Mikhailov' . ': ${you} must choose ${technique_choices}: ',
            transitions: [
                "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
            ],
        );
    }

    public function getArgs(): array
    {
        $this->game->theah->buildCity();

        // WHY: EventHub zeroes Technique Riposte when every combat card this round
        // is DashedRiposte — hide the option so the player is not offered a no-op.
        $riposteAvailable = ! $this->game->theah->currentRoundCombatCardsHaveDashedRiposte();

        // WHY: Prototype CAD — plain English fragments, no clienttranslate / no i18n arg
        // (hide from translators). Still use fixed strings (not sprintf) so the prompt reads cleanly.
        $techniqueChoices = $riposteAvailable ? '+1 Riposte or Lethal' : 'Lethal';

        return [
            'technique_choices' => $techniqueChoices,
            'riposteAvailable' => $riposteAvailable,
        ];
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Default Choice is Riposte — fall back to Lethal when dashed.
        if (! $this->game->theah->currentRoundCombatCardsHaveDashedRiposte())
        {
            $this->game->gamestate->nextState();
            return;
        }

        $this->game->actFromCardWithId((string) Technique_05DabneyUS01::CHOICE_LETHAL);
    }
}
