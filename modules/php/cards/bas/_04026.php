<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class _04026 extends FactionAttachment implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pompon");
        $this->Title = clienttranslate("Dainty Lapdog");
        $this->Image = "04026.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 26;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 1;

        $this->ResolveModifier = 1;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate("Animal"),
            clienttranslate("Pet"),
            clienttranslate("Dog"),
            clienttranslate("Unique")
        ];

        $this->Text = clienttranslate("<p>May only equip to your <b>Diplomat</b>.</p>
<p><b>Reaction:</b> When a pressure at this location would succeed by one or fewer, engage this card • It fails instead.</p>
<p><b>Technique:</b> Engage this card • +1[Parry].</p>");

        $this->resetCard();

        $this->InPlayXImageOffset = -20;

        $this->Reactions = [
            new Reaction_04026(),
        ];

        $this->Techniques = [
            new Technique_04026(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if (! $character->hasTrait("Diplomat"))
            {
                throw new UserException($event->theah->game->translate("Pompon can only be equipped to a Diplomat."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character))
        {
            return false;
        }

        return $character->hasTrait("Diplomat");
    }
}
