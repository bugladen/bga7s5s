<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03023 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Heal a Wound");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        foreach ($performers as $performer)
        {
            if ($performer->Wounds >= 2)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter($performers, fn(Character $p) => $p->Wounds >= 2));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            $healEvent = EventFactory::createCharacterBeingHealedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($healEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
