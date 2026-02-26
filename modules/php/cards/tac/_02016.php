<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02016;

class _02016 extends FactionAttachment implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cross of the Martyrs");
        $this->Image = "02016.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 16;

        $this->initializeFaction("Eisen");

        $this->ResolveModifier = 1;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            "Holy",
            "Relic",
            "Battle Standard",
            "Unique",
        ];

        $this->Reactions = [
            new Reaction_02016(),
        ];

        $this->resetCard();
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->hasTrait("Sorcerer"))
            {
                throw new UserException($event->theah->game->translate("The Cross of the Martyrs can not be equipped to Sorcerers."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->addTrait($event->theah->game, "Zealot");
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->removeTrait($event->theah->game, "Zealot");
        }
    }
}