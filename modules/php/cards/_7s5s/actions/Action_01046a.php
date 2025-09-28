<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01046a extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound and Move to Adjacent Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $darkGift = $this->getOwningCard($theah);
        if ($darkGift->Engaged)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $darkGift = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($darkGift->ControllerId, $darkGift->Id, "01046a", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01046a)
        {
            $attachedTo = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $attachedTo->Id;

            $args["locationIds"] = $game->theah->getAdjacentCityLocations($attachedTo->Location);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01046a)
        {
            $location = $ids[0];
            $darkGift = $this->getOwningCard($game->theah);

            $locations = $game->theah->getAdjacentCityLocations($darkGift->Location);
            if ( ! in_array($location, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not adjacent to %s."), $location, $darkGift->Location));
            }

            $attachedTo = $this->getOwningCharacter($game->theah);

            $engageEvent = EventFactory::createCardEngagedEvent($darkGift->ControllerId, $darkGift->Id, $darkGift->Id);
            $game->theah->eventCheck($engageEvent);

            $woundEvent = EventFactory::createCharacterWoundedEvent($attachedTo->Id, $darkGift->Id, 1, sprintf($game->translate("%s Action"), $darkGift->getInjectCode()), $this->Id);
            $game->theah->eventCheck($woundEvent);

            $moveEvent = EventFactory::createCardMovedEvent($attachedTo->ControllerId, $attachedTo->Id, $attachedTo->Location, $location, $engage = false, $darkGift->Id);
            $game->theah->eventCheck($moveEvent);

            $game->theah->queueEvent($engageEvent);
            $game->theah->queueEvent($woundEvent);
            $game->theah->queueEvent($moveEvent);

            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("locationChosen");
        }
    }
}