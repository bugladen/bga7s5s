<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04055a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04055b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class _04055 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Sturdy Shield');
        $this->Image = '04055.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 55;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 1;
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        // WHY: Printed Offhand — does not count against one Armor / one Weapon;
        // still limited to one Offhand per character (UtilitiesTrait / Reaction_AttachmentTypeLimit).
        $this->OffHand = true;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Armor'),
            clienttranslate('Shield')
        ];

        $this->Text = clienttranslate("<p>Offhand <i>(Offhand attachments do not count against the limit of one Armor and one Weapon per character. Limit one attachment with offhand per character.)</i></p>
<p>May only equip to your character with 2[Combat] or more.</p>
<p><b>Technique:</b> Engage this card • +1[Parry]</p>
<p><b>Gambling Technique:</b> +1[Parry]</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_04055a(),
            new Technique_04055b(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->ModifiedCombat < 2)
            {
                throw new UserException($event->theah->game->translate("Sturdy Shield can only be equipped to a character with 2 or more Combat."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $character->ModifiedCombat >= 2;
    }
}
