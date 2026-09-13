<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04036a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04036b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class _04036 extends FactionAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ciphered Tome");
        $this->Title = clienttranslate("Illuminated Codex");
        $this->Image = "04036.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 36;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate("Alquimia"),
            clienttranslate("Codex"),
            clienttranslate("Unique")
        ];

        $this->Text = clienttranslate("<p>May only equip to your <b>Academic</b>.</p>
<p><b>City Action:</b> Engage this card • Move a Renown from this location to another <b>City</b> location.</p>
<p><b>City Action:</b> Engage this card • Move your performer to another location with more Renown.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04036a(),
            new Action_04036b(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if (! $character->hasTrait("Academic"))
            {
                throw new UserException($event->theah->game->translate("Ciphered Tome can only be equipped to an Academic."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $character->hasTrait("Academic");
    }
}
