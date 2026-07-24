<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;

class _04cd19 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Blood in the Water');
        $this->Image = '04cd19.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 0;

        $this->InPlayXImageOffset = -10;

        $this->CityCardNumber = 19;

        $this->Traits = [
            clienttranslate('Catastrophe')
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> When this card is revealed • Add a Renown to this location.</p>
<p><b>Forced:</b> After a character at this location becomes engaged • Wound them.</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Forced: When this card is revealed • Add a Renown to this location.
        // WHY: Reveal gate is EventCityCardAddedToLocation + cardId — do not require
        // cardInCity. Use $event->location (not $this->Location) so mid-placement is safe.
        if ($event instanceof EventCityCardAddedToLocation && $event->cardId == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", clienttranslate(
                '${card_inject_code}: Add a Renown to ${location_name}.'), [
                'i18n' => ['location_name'],
                'card_inject_code' => $this->getInjectCode(),
                'location_name' => $event->location,
            ]);

            $renownEvent = EventFactory::createRenownAddedToLocationEvent(
                $this->ControllerId,
                $event->location,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($renownEvent);

            return;
        }

        // Forced: After a character at this location becomes engaged • Wound them.
        // WHY: EventCardEngaged covers characters (and attachments); filter to Character
        // at this location. Mirror Legion's Caress (_01021) wound queue. Check canceled
        // so impervious cancelers (e.g. Maryam) that ran earlier in the same pass skip us.
        if ($event instanceof EventCardEngaged
            && ! $event->canceled
            && $event->theah->cardInCity($this))
        {
            $character = $event->theah->getCharacterById($event->cardId);
            if ($character === null || $character->Location != $this->Location)
            {
                return;
            }

            $game = $event->theah->game;
            $game->notify->all("message", clienttranslate(
                '${card_inject_code}: ${character_inject_code} becomes engaged and is wounded.'), [
                'card_inject_code' => $this->getInjectCode(),
                'character_inject_code' => $character->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $character->Id,
                $this->Id,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($woundEvent);
        }
    }
}
