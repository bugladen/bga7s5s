<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;

class _02037 extends FactionAttachment implements IHasTechniques, IHasReactions
{
    use TechniqueTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Mysta');
        $this->Image = "02037.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 37;

        $this->initializeFaction('Castille');

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 3;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Animal'),
            clienttranslate('Cat'),
            clienttranslate('Menace'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> When this card would be put in the discard pile, sink it instead.</p><p><b>Technique:</b> The adversary cannot gamble during their next round.</p><p><b>Reaction:</b> After an opposing character issues a challenge • Equip this card to your character at this location.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_02037()
        ];

        $this->Reactions = [
            new Reaction_02037(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (($event instanceof EventCardDiscardedFromHand || $event instanceof EventCardDiscardedFromPlay) && $event->cardId === $this->Id)
        {
            $event->canceled = true;

            $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($this->ControllerId, $this->Id, false);
            $event->queueEvent($sinkEvent);
        }
    }
}
