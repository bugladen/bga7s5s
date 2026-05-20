<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01112a extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Make Location Uncontrolled");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        if (count($this->getEligiblePerformers($playerId, $theah)) == 0)
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        $firstPlayerId = $theah->game->globals->get(Game::FIRST_PLAYER, false);

        return $owner->ControllerId == $firstPlayerId;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter(
            $performers,
            fn($character) => ! $character->Engaged
                && $theah->canLocationBecomeUncontrolledBy($playerId, $character->Location)
        ));

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            $owner = $this->getOwningCard($event->theah);

            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);

            if ($event->theah->canLocationBecomeUncontrolledBy($performer->ControllerId, $performer->Location))
            {
                $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent($performer->ControllerId, $performer->Location);
                $event->theah->queueEvent($uncontrolledEvent);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${location} cannot become uncontrolled.'), [
                    'i18n' => ['location'],
                    'location' => $performer->Location,
                ]);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
