<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

class _04010 extends Risk implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Unravel the Thread");
        $this->Image = "04010.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 10;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 0;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate("Sorcery"),
            clienttranslate("Sorte")
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer Reaction:</b> When your performer reveals this card while gambling • Reveal additional cards equal to their [Influence]. Their <b>Sorceries</b> gain +1[Parry] this round.</p>
<p><b>Sorcerer Action:</b> Sink up to two cards from a single discard pile. Then, draw a card and sink this card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04010(),
        ];

        $this->Reactions = [
            new Reaction_04010(),
        ];
    }

    // WHY: Faction-deck cards are not in buildCity(). FrameworkActionsTrait loads a fresh
    // instance for actFromCard*; setUsed→getCardById would load a second copy and persist
    // Used=false. Pin this instance into the world first so setUsed writes the right object.
    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        $game->theah->addCardToWorld($this);
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        $game->theah->addCardToWorld($this);
        parent::actFromCardPass($game, $state, $stateName, $internalId);
    }
}
