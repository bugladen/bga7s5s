<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class _04053 extends FactionAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Leather Spaulders');
        $this->Image = '04053.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 53;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 1;
        $this->ResolveModifier = 1;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->DashedParry = false;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Armor'),
        ];

        $this->Text = clienttranslate("<p>May only equip to your character with 2[Finesse] or more.</p>
<p><b>Reaction:</b> When an opponent's ability would wound the equipped character, engage this card • Ignore that wound. <i>(The wound is not taken.)</i></p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04053(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->ModifiedFinesse < 2)
            {
                throw new UserException($event->theah->game->translate("Leather Spaulders can only be equipped to a character with 2 or more Finesse."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $character->ModifiedFinesse >= 2;
    }
}
