<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02047 extends AttachmentAction implements IAbilityThatTargetsCards
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Send Attachment to The Locker");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $owner = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($owner))
            return false;

        $attachments = $theah->getAvailableAttachmentsAtLocation($owner->Location);

        return count($attachments) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningAttachment($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02047", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02047)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;

            $attachments = $game->theah->getAvailableAttachmentsAtLocation($owner->Location);

            $args['attachmentsInPlay'] = array_map(fn($a) => $a->Id, $attachments);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02047)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if (! $attachment)
            {
                throw new UserException($game->translate("Invalid attachment"));
            }

            $owner = $this->getOwningCharacter($game->theah);

            if ($attachment->Location != $owner->Location)
            {
                throw new UserException($game->translate("Attachment is not at this location."));
            }

            if ($attachment->isAttached() || $attachment->isControlled())
            {
                throw new UserException($game->translate("Attachment is not available."));
            }

            $isArtifact = $attachment->hasTrait("Artifact");
            $owningAttachment = $this->getOwningAttachment($game->theah);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($owner->ControllerId, $attachment->Id);
            $game->theah->queueEvent($lockerEvent);

            if ($isArtifact)
            {
                $reknownEvent = EventFactory::createReknownAddedToLocationEvent(
                    $owner->ControllerId,
                    $owner->Location,
                    1,
                    $owningAttachment->getInjectCode()
                );
                $game->theah->queueEvent($reknownEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
