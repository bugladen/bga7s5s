<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Excalibur;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Excalibur;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class _05Excalibur extends FactionAttachment implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Dietrich Blade";
        $this->Title = "Honored Heirloom";
        $this->Image = "05Excalibur.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 27;

        $this->initializeFaction("Eisen");

        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            "Weapon",
            "Melee",
            "Sword",
            "Relic",
            "Unique"
        ];

        $this->Text = "<p>The equipped character gains <b>Knight</b></p>
        <p><b>Reaction:</b> When the equipped performer issues a challenge • Wound the performer and target opposing character with equal or greater [Combat].</p>
        <p><b>Technique:</b> +1 [Riposte].</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Excalibur(),
        ];

        $this->Techniques = [
            new Technique_05Excalibur(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Pattern B pair — Text grants Knight while equipped; unequip must clear or the trait leaks.
        if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->addTrait($event->theah->game, "Knight");
        }

        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $character->removeTrait($event->theah->game, "Knight");
        }
    }
}
