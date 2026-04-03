<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02032;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseDawnBeginning;

class _02032 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        
        
        $this->Name = clienttranslate('Lucas Martinez “Damned”');
        $this->Title = clienttranslate('Damned');

        $this->Image = '02032.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 32;

        $this->initializeFaction('Castille');
        $this->Resolve = 3;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Traits = [
            clienttranslate('Monster'),
            clienttranslate('Undead'),
            clienttranslate('Pirate'),
            clienttranslate('Castille'),
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> Lucas Martinez “Damned” cannot be in an Approach deck and can only be mustered from <b>The Locker</b> instead.</p><p><b>Reaction:</b> After Lucas musters, put your risk from <b>The Locker</b> into your hand.</p><p><b>Forced:</b> At the beginning of Dawn, destroy Lucas.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02032(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPhaseDawnBeginning)
        {
            $destroyEvent = EventFactory::createCharacterDestroyedEvent($this->ControllerId, $this->Id, 'Dawn Phase');
            $event->theah->queueEvent($destroyEvent);
        }
    }
}