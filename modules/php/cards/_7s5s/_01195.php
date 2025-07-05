<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _01195 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Eager Blade');
        $this->Image = "img/cards/7s5s/195.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 195;
        
        $this->CityCardNumber = 19;
        $this->WealthCost = 1;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 1;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            'Corruption',
            'Weapon',
            'Melee',
            'Sword',
            'Unique',
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // If placed in the discard pile, move to the bazaar.
        if ($event instanceof EventCardAddedToCityDiscardPile && $event->cardId === $this->Id)
        {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('Eager Blade was discarded.  Its ability will trigger and it will be moved to the Bazaar.'), []);

            $moveEvent = EventFactory::createCityCardAddedToLocationEvent($this->Id, Game::LOCATION_CITY_BAZAAR);
            $event->queueEvent($moveEvent);
        }

        // If Eager Blade is in play, add 1 to the riposte.
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->isAttached() && $this->AttachedToId == $event->actorId)
        {
            $event->riposte += 1;
            $event->explanations[] = $event->theah->game->translate('+1 Riposte from Eager Blade.');

            $event->theah->game->notifyAllPlayers("message", clienttranslate('Eager Blade was used with a combat card.  Its ability will trigger and it will be destroyed.'), []);

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $this->AttachedToId, $this->Id);
            $event->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($this->ControllerId, $this->Id, $this->Location);
            $event->queueEvent($discardEvent);
        }
    }
}