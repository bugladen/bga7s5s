<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Reaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

//Base class for reactions that are attached to a card as opposed to a game framework reaction.
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
            $this->setUsed($event->theah, false);
        }
    }

    public function getReactionDescription(Theah $theah): string
    {
        $owner = $this->getOwningCard($theah);
        return parent::getReactionDescription($theah) . $owner->Name  . " > " . $theah->game->translate("Reaction") . ": ";
    }

    public function getReactionPayForDescription(Theah $theah): string
    {
        return '${you} must now select cards to pay for ' . $this->Name . ': ';
    }
    
    public function reactionPaidFor(Game $game, int $state, string $internalId, string $reactionId): void 
    {
        $this->setUsed($game->theah, true);
    }

}