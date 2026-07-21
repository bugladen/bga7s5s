<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03051 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move to Leader's Location, Then En Garde Attachment");
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

        $leader = $theah->getLeaderByPlayerId($playerId);
        if ($leader === null)
        {
            return false;
        }

        if ($owner->Location == $leader->Location)
        {
            return false;
        }

        return count($this->getEngardeableAttachments($theah, $owner, $leader->Location)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $leader = $event->theah->getLeaderByPlayerId($owner->ControllerId);
            if ($leader === null)
            {
                return;
            }

            // WHY engage=false: City Action prints no Engage cost; relocation only.
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $leader->Location,
                $engage = false,
                $owner->Id,
                $this->Id
            );
            $event->theah->queueEvent($moveEvent);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03051", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03051)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);

            $args['performerId'] = $owner->Id;

            $attachments = [];
            if ($leader !== null)
            {
                foreach ($this->getEngardeableAttachments($game->theah, $owner, $leader->Location) as $attachment)
                {
                    $attachments[] = [
                        'id' => $attachment->Id,
                        'name' => $game->translate($attachment->Name),
                    ];
                }
            }
            $args['attachments'] = $attachments;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03051)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);
            if ($leader === null)
            {
                throw new UserException($game->translate("You no longer have a Leader in play."));
            }

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null || $attachment->FakeAttachment || ! $attachment->Engaged)
            {
                throw new UserException($game->translate("Choose one of your engaged attachments at the Leader's location."));
            }

            $validIds = array_map(
                fn(Attachment $a) => $a->Id,
                $this->getEngardeableAttachments($game->theah, $owner, $leader->Location)
            );
            if (! in_array($attachment->Id, $validIds, true))
            {
                throw new UserException($game->translate("Choose one of your engaged attachments at the Leader's location."));
            }

            $engardeEvent = EventFactory::createCardEngardedEvent($owner->ControllerId, $attachment->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engardeEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("attachmentChosen");
        }
    }

    /**
     * Engaged non-Fake attachments controlled by the player that will be at the
     * destination after Yepikhodov moves there (his own engaged attachments +
     * engaged attachments already on your characters at that location).
     *
     * @return Attachment[]
     */
    private function getEngardeableAttachments(Theah $theah, Character $owner, string $destination): array
    {
        $attachments = [];

        foreach ($owner->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($this->isEngardeable($attachment, $owner->ControllerId))
            {
                $attachments[] = $attachment;
            }
        }

        $characters = $theah->getCharactersAtLocation($destination);
        foreach ($characters as $character)
        {
            if ($character->ControllerId != $owner->ControllerId || $character->Id == $owner->Id)
            {
                continue;
            }

            foreach ($character->Attachments as $attachmentId)
            {
                $attachment = $theah->getAttachmentById($attachmentId);
                if ($this->isEngardeable($attachment, $owner->ControllerId))
                {
                    $attachments[] = $attachment;
                }
            }
        }

        return $attachments;
    }

    private function isEngardeable(?Attachment $attachment, int $playerId): bool
    {
        return $attachment !== null
            && ! $attachment->FakeAttachment
            && $attachment->Engaged
            && $attachment->ControllerId == $playerId;
    }
}
