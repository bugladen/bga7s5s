<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Reaction extends CardAbility
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->Used = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }
    }

    public function isAvailable(): bool
    {
        return ! $this->Used;
    }

    public function getStateDescription(Theah $theah): string
    {
        return '';
    }

    public function getButtonProperties(Theah $theah): array
    {
        return [];
    }

    public function createButtonProperty(string $text, string $reaction): array
    {
        return [
            'text' => $text,
            'reaction' => $reaction,
        ];
    }
}