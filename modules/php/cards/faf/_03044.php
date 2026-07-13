<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class _03044 extends FactionAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Torres Cloak");
        $this->Image = '03044.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 44;

        $this->InPlayXImageOffset = -20;

        $this->initializeFaction('Castille');

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->OffHand = true;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Melee'),
            clienttranslate('Cloak'),
            clienttranslate('Torres')
        ];

        $this->Text = clienttranslate("<p><b>Offhand</b></p>
<p>May only equip to your Duelist.</p>
<p><b>Reaction:</b> When the equipped participant's adversary would resolve a <b>Maneuver</b> or <b>Technique</b>, engage this card • Cancel the effects unless they discard a card.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03044(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if (! $character->hasTrait("Duelist"))
            {
                throw new UserException($event->theah->game->translate("Torres Cloak can only be equipped to a Duelist."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $character->hasTrait("Duelist");
    }
}
