<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01043 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Toggle Mercenary Trait';
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to have Uwe be a Mercenary: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Make Uwe a Mercenary'), 'makeUweAMercenary');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('DO NOT Make Uwe a Mercenary'), 'doNotMakeUweAMercenary');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

    }
}