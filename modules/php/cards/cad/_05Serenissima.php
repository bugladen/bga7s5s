<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Serenissima;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Serenissima;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _05Serenissima extends Leader implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Patricia Moustakas";
        $this->Title = "Tickets, Please.";
        $this->Image = "05Serenissima.v5.jpg";
        $this->InPlayXImageOffset = -20;
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=16. Robin attachment also uses 16 but has no HD/duel picker
        // states — 405016 / 52105016 are free (same Corncob vs Snow White collision pattern).
        $this->CardNumber = 16;

        $this->initializeFaction("Castille");

        $this->Resolve = 6;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->Traits = [
            "Leader",
            "Hero",
            "Academic",
            "Dockmaster",
            "Numa"
        ];

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Text = "<p>You may consider Patricia to have any trait when you use abilities or pay costs.</p>
<p><b>Covert Action:</b> Send a <b>Bureaucracy</b> or <b>Discovery</b> in your discard pile to The Locker • En garde target character.</p>
<p><b>Technique:</b> Discard a card • +1 [Parry]. The adversary discards a card at random.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Serenissima(),
        ];

        $technique = new Technique_05Serenissima();
        $technique->setId("Technique_05Serenissima");
        $this->Techniques = [
            $technique,
        ];
    }

    // WHY hasTrait override (Uwe _01043 shape): trait checks during ability/cost selection
    // run before events, so Reactions cannot invent "any trait". Callers that mean "while
    // using this ability" pass themselves as $queryCard; null query = printed traits only
    // (opponent "is she a Diplomat?" gates must not see fiction). Same-controller gate =
    // printed "when you use". Blanked = Fate's Silence lifts the fiction.
    public function hasTrait(string $trait, ?Card $queryCard = null): bool
    {
        if ($queryCard !== null
            && ! $this->abilitiesAreBlanked()
            && $this->ControllerId > 0
            && $queryCard->ControllerId == $this->ControllerId)
        {
            return true;
        }

        return parent::hasTrait($trait, $queryCard);
    }
}
