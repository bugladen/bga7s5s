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

            description: clienttranslate('${actplayer} is choosing options to perform a Technique.'),
            // WHY: ${technique_choices} swaps when combat card(s) have dashed Riposte/Parry.
            descriptionMyTurn: clienttranslate('Valeri Mikhailov') . clienttranslate(': ${you} must choose ${technique_choices}: '),
            transitions: [
                "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
            ],
        );
    }

    public function getArgs(): array
    {
        $this->game->theah->buildCity();

        // WHY: EventHub zeroes Technique Riposte/Parry when every combat card this round
        // is dashed on that axis — hide the option so the player is not offered a no-op.
        $riposteAvailable = ! $this->game->theah->currentRoundCombatCardsHaveDashedRiposte();
        $parryAvailable = ! $this->game->theah->currentRoundCombatCardsHaveDashedParry();

        // WHY: Fixed clienttranslate strings (not sprintf of English fragments) for i18n.
        if ($parryAvailable && $riposteAvailable)
        {
            $techniqueChoices = clienttranslate('+1 Parry, +1 Riposte, or Lethal');
        }
        else if ($parryAvailable)
        {
            $techniqueChoices = clienttranslate('+1 Parry or Lethal');
        }
        else if ($riposteAvailable)
        {
            $techniqueChoices = clienttranslate('+1 Riposte or Lethal');
        }
        else
        {
            $techniqueChoices = clienttranslate('Lethal');
        }

        return [
            'i18n' => ['technique_choices'],
            'technique_choices' => $techniqueChoices,
            'riposteAvailable' => $riposteAvailable,
            'parryAvailable' => $parryAvailable,
        ];
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    public function zombie(int $playerId): void
    {
        // WHY: Default Choice is Riposte — fall back Parry, then Lethal, when dashed.
        if (! $this->game->theah->currentRoundCombatCardsHaveDashedRiposte())
        {
            $this->game->gamestate->nextState();
            return;
        }

        if (! $this->game->theah->currentRoundCombatCardsHaveDashedParry())
        {
            $this->game->actFromCardWithId((string) Technique_05DabneyUS01::CHOICE_PARRY);
            return;
        }

        $this->game->actFromCardWithId((string) Technique_05DabneyUS01::CHOICE_LETHAL);
    }
}
