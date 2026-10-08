<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01174 extends RiskAction implements IAbilityThatTargetsCards
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Destroy Non-Unique Attachment");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $cards = array_filter($theah->getCardsInPlay(), fn($card) => $card instanceof Attachment && ! $card->hasTrait('Unique'));

        return count($cards) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01174", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Do not queue unequip/discard beside the cancel hook. Unyielding Loyalty
        // text is "your cards" — attachment Id is the target. Queuing destroy first meant
        // UL never saw a hook (and even if it intercepted a later event, unequip would
        // already have applied). Amour / Solvente / Giacinto shape: gate effects on
        // EventCharacterTargeted surviving. Pass / Night of Drinking re-queue the clone.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            $attachment = $event->theah->getAttachmentById($event->targetId);
            if ($owner === null || $attachment === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $event->theah->game->getNextEventBatchId();

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                $attachment->ControllerId,
                $attachment->AttachedToId,
                $attachment->Id
            );
            $unequipEvent->batchId = $batchId;
            $event->theah->eventCheck($unequipEvent);
            $event->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createAttachmentDiscardedFromPlayEvent($attachment, $owner->Id, $asEffect = true);
            $discardEvent->batchId = $batchId;
            $event->theah->queueEvent($discardEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01174)
        {
            $attachments = array_filter($game->theah->getCardsInPlay(), fn($card) => $card instanceof Attachment && ! $card->hasTrait('Unique'));
            $availableAttachments = [];
            foreach ($attachments as $attachment)
            {
                // WHY: Multiple copies of the same attachment can be in play — include
                // controller + host so the button list is unambiguous.
                $character = $attachment->attachedTo($game->theah);
                if ($character !== null)
                {
                    $playerName = $game->getPlayerNameById($character->ControllerId);
                    $name = sprintf(
                        $game->translate('%s (%s\'s %s)'),
                        $attachment->Name,
                        $playerName,
                        $character->Name
                    );
                }
                else
                {
                    $name = $attachment->Name;
                }

                $availableAttachments[] = ['id' => $attachment->Id, 'name' => $name];
            }
            
            $args["attachments"] = $availableAttachments;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01174)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if (! $attachment)
            {
                throw new \BgaUserException($game->translate("Invalid attachment"));
            }

            if (!$attachment->isControlled())
            {
                throw new \BgaUserException($game->translate("Attachment is not in play"));
            }

            if ($attachment->Location == Game::LOCATION_HAND)
            {
                throw new \BgaUserException($game->translate("Attachment is in hand"));
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY: targetId is the attachment (printed target), not the host. UL /
            // Hexenjagd resolve via getCardById. Shared batchId so cancel strips
            // the effect package queued after the hook survives.
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $attachment->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}
