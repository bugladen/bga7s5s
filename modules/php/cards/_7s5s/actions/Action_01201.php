<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01201 extends CharacterAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound and Draw a Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $ravenna = $this->getOwningCard($theah);

        if (! $ravenna->isControlled())
        {
            return false;
        }

        if (! $ravenna->hasTrait("Sorcerer"))
        {
            return false;
        }

        return $theah->cardInCity($ravenna);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $ravenna = $this->getOwningCard($event->theah);

            $this->announceAction($event->theah->game);

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($ravenna->ControllerId, $ravenna->Id, $this->Id, $ravenna->Id);
            $event->theah->queueEvent($sorceryStartEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($ravenna->Id, $ravenna->Id, 1, $ravenna->getInjectCode(), $this->Id);
            $event->theah->eventCheck($woundEvent);
            $event->theah->queueEvent($woundEvent);

            $addEvent = EventFactory::createCardDrawnEvent($event->playerId, $ravenna->getInjectCode());
            $event->theah->queueEvent($addEvent);

            $this->setUsed($event->theah, true);
            $this->resetPlayerPassCount($event->theah->game);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($ravenna->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
            
            $sorcererEvent = EventFactory::createSorcererAbilityPlayedEvent($ravenna->ControllerId, $ravenna->Id, $this->Id, $ravenna->Id, $ravenna->Id, $ravenna->Location);
            $event->theah->queueEvent($sorcererEvent);

        }
    }
}