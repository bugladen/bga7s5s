<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class _03007 extends FactionAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Matushka's Shears");
        $this->Title = clienttranslate("Severing the Strand");
        $this->Image = "03007.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 7;

        $this->initializeFaction("Vodacce");

        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 1;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            clienttranslate("Weapon"),
            clienttranslate("Melee"),
            clienttranslate("Dar Matushki"),
            clienttranslate("Unique")
        ];

        $this->Text = clienttranslate("<p>May only equip to your <b>Strega</b>.</p><p><b>Sorcerer City Reaction:</b> When an opposing character is sent to <b>The Locker</b>, engage this card • Their controller wounds their <b>Leader</b> unless they sink two cards from their hand.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03007(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if (! $character->hasTrait("Strega"))
            {
                throw new UserException($event->theah->game->translate("Matushka's Shears can only be equipped to a Strega."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $character->hasTrait("Strega");
    }
}
