<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03063 extends SchemeCityAction implements IAbilityThatTargetsCards
{
    private const MOVE_RENOWN = 1;
    private const MOVE_ATTACHMENT = 2;

    // WHY: Persist branch across HD sub-states; flush via updateCardObjectInDb (03029/03062).
    public int $MoveMode = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Renown or available attachment");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        // WHY: "Scoundrel City Action" is a mechanical trait gate, not ISorcererAbility.
        // Full legality: location must have something to move (Renown or available attachment).
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Scoundrel")
                && $this->locationHasMovableThing($theah, $performer->Location)
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($performer === null || $performer->ControllerId != $event->playerId)
            {
                throw new UserException($game->translate("Invalid performer"));
            }

            if (! $performer->hasTrait("Scoundrel"))
            {
                throw new UserException($game->translate("Performer must be a Scoundrel."));
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException($game->translate("Performer must be at a City location."));
            }

            if (! $this->locationHasMovableThing($event->theah, $performer->Location))
            {
                throw new UserException($game->translate("Nothing to move from the performer's location."));
            }

            $this->MoveMode = 0;
            $game->globals->set(Game::CHOSEN_CARD, 0);
            $game->updateCardObjectInDb($owner);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03063", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03063)
        {
            $args["performerId"] = $performerId;
            $args["renownAvailable"] = $performer !== null
                && $this->locationHasRenown($game->theah, $performer->Location);
            $args["attachmentsInPlay"] = $performer !== null
                ? array_map(
                    fn(Attachment $attachment) => $attachment->Id,
                    $game->theah->getAvailableAttachmentsAtLocation($performer->Location)
                )
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03063_2)
        {
            $args["performerId"] = $performerId;
            $args["locationIds"] = $performer !== null
                ? $this->getValidDestinationLocations($game->theah, $performer->Location)
                : [];
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03063)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            // WHY: id 0 = Move Renown. Card ids are never 0, so no collision with attachments.
            if ($id == 0)
            {
                if (! $this->locationHasRenown($game->theah, $performer->Location))
                {
                    throw new UserException($game->translate("There is no Renown at the performer's location."));
                }

                $this->MoveMode = self::MOVE_RENOWN;
                $game->globals->set(Game::CHOSEN_CARD, 0);
            }
            else
            {
                $attachment = $game->theah->getAttachmentById($id);
                if ($attachment === null)
                {
                    throw new UserException($game->translate("Invalid attachment"));
                }

                if ($attachment->Location != $performer->Location || $attachment->isAttached())
                {
                    throw new UserException($game->translate("Attachment is not available at the performer's location."));
                }

                $this->MoveMode = self::MOVE_ATTACHMENT;
                $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);
            }

            $game->updateCardObjectInDb($owner);
            $game->gamestate->nextState("thingChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03063_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $playerId = $game->getActivePlayerId();
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            $destination = $ids[0] ?? '';
            $validDestinations = $this->getValidDestinationLocations($game->theah, $performer->Location);
            if (! in_array($destination, $validDestinations, true))
            {
                throw new UserException($game->translate("Invalid destination location."));
            }

            if ($this->MoveMode == self::MOVE_RENOWN)
            {
                if (! $this->locationHasRenown($game->theah, $performer->Location))
                {
                    throw new UserException($game->translate("There is no Renown at the performer's location."));
                }

                $batchId = $game->getNextEventBatchId();

                $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
                    $playerId,
                    $performer->Location,
                    $destination,
                    1,
                    $owner->getInjectCode()
                );
                $movingEvent->batchId = $batchId;
                $game->theah->eventCheck($movingEvent);
                $game->theah->queueEvent($movingEvent);

                $removedEvent = EventFactory::createRenownRemovedFromLocationEvent(
                    $playerId,
                    $performer->Location,
                    1,
                    $owner->getInjectCode()
                );
                $removedEvent->batchId = $batchId;
                $game->theah->eventCheck($removedEvent);
                $game->theah->queueEvent($removedEvent);

                $addedEvent = EventFactory::createRenownAddedToLocationEvent(
                    $playerId,
                    $destination,
                    1,
                    $owner->getInjectCode(),
                    $isMove = true
                );
                $addedEvent->batchId = $batchId;
                $game->theah->eventCheck($addedEvent);
                $game->theah->queueEvent($addedEvent);
            }
            else if ($this->MoveMode == self::MOVE_ATTACHMENT)
            {
                $attachmentId = (int)$game->globals->get(Game::CHOSEN_CARD);
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment === null)
                {
                    throw new UserException($game->translate("Invalid attachment"));
                }

                if ($attachment->Location != $performer->Location || $attachment->isAttached())
                {
                    throw new UserException($game->translate("Attachment is not available at the performer's location."));
                }

                // WHY engage=false: moving an unattached city card; no engage printed.
                $moveEvent = EventFactory::createCardMovingEvent(
                    $playerId,
                    $attachment->Id,
                    $attachment->Location,
                    $destination,
                    false,
                    $owner->Id,
                    $this->Id
                );
                $game->theah->eventCheck($moveEvent);
                $game->theah->queueEvent($moveEvent);
            }
            else
            {
                throw new UserException($game->translate("Invalid move option."));
            }

            $this->MoveMode = 0;
            $game->globals->set(Game::CHOSEN_CARD, 0);
            $game->updateCardObjectInDb($owner);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    private function locationHasMovableThing(Theah $theah, string $location): bool
    {
        return $this->locationHasRenown($theah, $location)
            || count($theah->getAvailableAttachmentsAtLocation($location)) > 0;
    }

    private function locationHasRenown(Theah $theah, string $location): bool
    {
        $cityLocation = $theah->getCityLocation($location);
        return $cityLocation !== null && $cityLocation->Renown > 0;
    }

    /**
     * @return list<string>
     */
    private function getValidDestinationLocations(Theah $theah, string $fromLocation): array
    {
        return array_values(array_filter(
            array_map(fn($location) => $location->Name, $theah->getCityLocations()),
            fn(string $name) => $name != $fromLocation
        ));
    }
}
