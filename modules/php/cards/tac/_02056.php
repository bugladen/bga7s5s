<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02056;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class _02056 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Main Gauche');
        $this->Image = '02056.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 56;

        $this->initializeFaction('Neutral');

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Melee'),
            clienttranslate('Dagger'),
        ];

        $this->OffHand = true;
        $this->Text = clienttranslate("<p>Offhand <i>(Offhand attachments do not count against the limit of one Armor and one Weapon per character. Limit one attachment with Offhand per character.)</i></p><p>May only equip to your <b>Duelist</b>.</p><p><b>Technique:</b> +1[Parry]. If your participant has 3[Finesse] or more, +1[Riposte] instead.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_02056(),
        ];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id) {
            $performer = $event->theah->getCardById($event->characterId);
            if (!$performer->hasTrait("Duelist"))
            {
                throw new UserException($event->theah->game->translate("Main Gauche can only be equipped to Duelists."));
            }
        }
    }

    public function canAttachTo(Character $character): bool
    {
        if (! parent::canAttachTo($character)) {
            return false;
        }

        return $character->hasTrait("Duelist");
    }
}
