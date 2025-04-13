<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Reaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class CardReaction extends Reaction
{
    use CardAbilityTrait;

    public function __construct()
    {
        parent::__construct();
        $this->initializeAbility();
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

    public function getReactionPayForDescription(Theah $theah): string
    {
        return '${you} must now select cards to pay for ' . $this->Name . ': ';
    }
    
    public function reactionPaidFor(Game $game, int $state, string $internalId, string $reactionId): void {}

}