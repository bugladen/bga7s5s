<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03014;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;

class _03014 extends Character
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Kaspar Dietrich");
        $this->Title = clienttranslate("Iron Reforged");
        $this->Image = "03014.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 14;

        $this->initializeFaction("Eisen");

        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Zealot"),
            clienttranslate("Scion"),
            clienttranslate("General"),
            clienttranslate("Eisen")
        ];

        $this->Text = clienttranslate("<p>Opponents' abilities cannot wound or move wounds to Kaspar. <i>(Threat is still converted to wounds.)</i></p><p><b>Technique:</b> If Kaspar is equipped with an <b>Eisenfaust</b> attachment or there is an <b>Eisenfaust</b> card in his dueling line • Wound the adversary.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_03014(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if (! ($event instanceof EventCharacterBeingWounded))
        {
            return;
        }

        if ($event->characterId != $this->Id || $event->wounds <= 0)
        {
            return;
        }

        // WHY: "(Threat is still converted to wounds.)" — challenge-threat
        // conversion (StatesTrait line ~1500) emits the wound event with an empty
        // abilityId, so we only block events that flow from an ability.
        if ($event->abilityId == '')
        {
            return;
        }

        $source = $event->theah->getCardById($event->sourceId);
        if ($source == null || $source->ControllerId == 0 || $source->ControllerId == $this->ControllerId)
        {
            return;
        }

        $oldWounds = $event->wounds;
        $event->wounds = 0;

        $event->theah->game->notify->all("message", clienttranslate('${character_inject_code}: Opponents\' abilities cannot wound Kaspar. ${oldWounds} wound(s) ignored from ${source_inject_code}.'), [
            "character_inject_code" => $this->getInjectCode(),
            "source_inject_code" => $source->getInjectCode(),
            "oldWounds" => $oldWounds,
        ]);
    }
}
