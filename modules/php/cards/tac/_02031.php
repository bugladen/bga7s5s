<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;

class _02031 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Lucas Martinez “Doomed”');
        $this->Title = clienttranslate('Doomed');

        $this->Image = '02031.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 31;

        $this->initializeFaction('Castille');
        $this->Resolve = 3;
        $this->Combat = 2;
        $this->Finesse = 1;
        $this->Influence = 3;

        $this->Traits = [
            clienttranslate('Villain'),
            clienttranslate('Pirate'),
            clienttranslate('Castille'),
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> After Lucas Martinez “Doomed” is mustered, send Lucas Martinez “Damned” to <b>The Locker</b> from out of play.</p><p><b>Forced:</b> After this card is sent to <b>The Locker</b> •  Muster Lucas Martinez “Damned” at that location from <b>The Locker</b>.</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (($event instanceof EventCharacterMustered || $event instanceof EventApproachCharacterPlayed) && $event->characterId == $this->Id)
        {
            $game = $event->theah->game;
            $lockerName = $game->getPlayerLockerName($this->ControllerId);

            $card = $game->createCardInLocation('02032', $lockerName, $this->ControllerId, $this->ControllerId);
            $event->theah->addCardToWorld($card);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($this->ControllerId, $card->Id);
            $event->theah->queueEvent($lockerEvent);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $game = $event->theah->game;
            $lockerName = $game->getPlayerLockerName($this->ControllerId);

            $lockerCards = $event->theah->getCardObjectsAtLocation($lockerName);
            $damned = null;
            foreach ($lockerCards as $card)
            {
                if ($card instanceof _02032)
                {
                    $damned = $card;
                    break;
                }
            }

            if ($damned !== null)
            {
                $event->theah->addCardToWorld($damned);

                $removedFromLockerEvent = EventFactory::createCardRemovedFromLockerEvent($this->ControllerId, $damned->Id);
                $event->theah->queueEvent($removedFromLockerEvent);

                $musterEvent = EventFactory::createCharacterMusteredEvent($this->ControllerId, $damned->Id, $this->Location);
                $event->theah->queueEvent($musterEvent);
            }
        }
    }
}