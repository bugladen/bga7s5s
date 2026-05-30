<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;

class _03015 extends Character
{
    private bool $DuskResolvePenaltyApplied = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Joern Keitelsson');
        $this->Title = clienttranslate("Fury's Edge");

        $this->Image = '03015.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 15;

        $this->InPlayXImageOffset = -10;

        $this->initializeFaction('Eisen');
        $this->Resolve = 8;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;
        $this->Traits = [
            clienttranslate('Villain'),
            clienttranslate('Pirate'),
            clienttranslate('Berserker'),
            clienttranslate('Spy'),
            clienttranslate('Vesten')
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> After Joern musters • Wound him.</p>
<p>During Dusk, Joern has -3 Resolve. <i>(Characters are destroyed when their wounds equal their Resolve)</i></p>
<p>When Joern's challenge is refused, he heals a wound.</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (($event instanceof EventCharacterMustered || $event instanceof EventApproachCharacterPlayed) && $event->characterId == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${character_inject_code}: Forced — after Joern musters, he is wounded.'), [
                "character_inject_code" => $this->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($this->Id, $this->Id, 1, $this->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);
        }

        if ($event instanceof EventDuskPhaseBegin && ! $this->DuskResolvePenaltyApplied && $this->isControlled())
        {
            $this->ModifiedResolve -= 3;
            $this->DuskResolvePenaltyApplied = true;
            $this->IsUpdated = true;

            $event->theah->game->notify->all("message", clienttranslate('${character_inject_code}: During Dusk, Joern has -3 Resolve (now ${resolve}).'), [
                "character_inject_code" => $this->getInjectCode(),
                "resolve" => $this->ModifiedResolve,
            ]);

            // WHY: The destruction check in Character::handleEvent only runs on a wound
            // event. Reducing ModifiedResolve below current Wounds during Dusk would
            // otherwise leave Joern alive at Wounds >= Resolve. Mirror the unequip path
            // (EventHub.php ~251) and queue the destroy explicitly.
            if ($this->Wounds >= $this->ModifiedResolve && ! $this->IsDying)
            {
                $this->IsDying = true;
                $this->unEquipAllAttachments($event->theah);

                $destroyEvent = EventFactory::createCharacterDestroyedEvent($this->ControllerId, $this->Id, $this->getInjectCode());
                $event->theah->queueEvent($destroyEvent);
            }
        }

        if ($event instanceof EventDuskEndOfDay && $this->DuskResolvePenaltyApplied)
        {
            $this->ModifiedResolve += 3;
            $this->DuskResolvePenaltyApplied = false;
            $this->IsUpdated = true;
        }

        if ($event instanceof EventChallengeRejected && $event->challengerId == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${character_inject_code}: Joern\'s challenge was refused — he heals a wound.'), [
                "character_inject_code" => $this->getInjectCode(),
            ]);

            $healEvent = EventFactory::createCharacterBeingHealedEvent($this->Id, $this->Id, 1, $this->getInjectCode(), $this->Id);
            $event->theah->queueEvent($healEvent);
        }
    }
}
