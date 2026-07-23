<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd04;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04cd04 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Astrid de Martinez');
        $this->Title = clienttranslate('Prideful Collaborator');
        $this->Image = '04cd04.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->InPlayXImageOffset = -20;

        $this->CityCardNumber = 4;

        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->WealthCost = 4;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Diplomat'),
            clienttranslate('Castille'),
        ];

        $this->Text = clienttranslate("<p>Negotiable <i>(You may Parley while paying for this card.)</i></p>
<p><i>En Garde</i> — Astrid gains +1[Influence] during pressures initiated by an opponent.</p>
<p><b>Action:</b> Engage Astrid • An adjacent location becomes uncontrolled. Move Astrid there.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04cd04(),
        ];
    }

    public function getInfluencePressureValue(Theah $theah, string $location): int
    {
        $value = parent::getInfluencePressureValue($theah, $location);

        // WHY: Italic En Garde is a precondition (Engaged=false). Opponent-initiated only
        // via PRESSURING_PLAYER. This hook is only called when Influence is a pressure
        // stat — Combat/Finesse pressures never see the bonus (do not also override
        // getCombatPressureValue / getFinessePressureValue).
        $pressuringPlayerId = (int) $theah->game->globals->get(Game::PRESSURING_PLAYER, 0);
        if (
            ! $this->Engaged
            && $pressuringPlayerId != 0
            && $pressuringPlayerId != $this->ControllerId
        )
        {
            $value += 1;
        }

        return $value;
    }
}

