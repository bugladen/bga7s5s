<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01017 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Alcee, Engage Character at Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($owner->Location);
        $characters = array_filter($characters, fn($character) => $character->Id != $owner->Id);
        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01017", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01017)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $args["performerId"] = $owner->Id;

            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->Id != $owner->Id));

            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;

    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01017)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new \BgaUserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }

            if ($target->Location != $owner->Location)
            {
                throw new \BgaUserException($game->translate("Target character is not at the same location as Alcee."));
            }

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            // $this->setUsed not called because card is destroyed

            $owner->unEquipAllAttachments($game->theah);
            $event = EventFactory::createCharacterDestroyedEvent($owner->ControllerId, $owner->Id, $owner->getInjectCode());
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardEngagedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }
    
}