<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_AttachmentTypeLimit extends GameReaction
{
    private const LIMITED_TYPES = ['Weapon', 'Armor', 'Attire'];

    public function __construct()
    {
        parent::__construct();

        $this->Id = 'Reaction_AttachmentTypeLimit';
        $this->Name = 'Attachment Type Limit';
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character instanceof Character && $this->characterExceedsAttachmentLimit($event->theah, $character))
            {
                // WHY: Character controller chooses — they own the board state that is over the limit.
                $transition = EventFactory::createReactionTransitionEvent($character->ControllerId, Game::THEAH_ID, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $playerId = $theah->game->getActivePlayerId();
        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $seenIds = [];
        foreach ($characters as $character)
        {
            foreach (self::LIMITED_TYPES as $type)
            {
                $attachments = $this->getLimitedAttachmentsOfType($theah, $character, $type);
                if (count($attachments) > 1)
                {
                    foreach ($attachments as $attachment)
                    {
                        if (isset($seenIds[$attachment->Id]))
                        {
                            continue;
                        }
                        $seenIds[$attachment->Id] = true;
                        $array[] = $this->createButtonProperty(
                            $theah->game,
                            sprintf($theah->game->translate('Discard %s'), $attachment->Name),
                            'discard_' . $attachment->Id
                        );
                    }
                }
            }

            $offHands = $this->getOffHandAttachments($theah, $character);
            if (count($offHands) > 1)
            {
                foreach ($offHands as $attachment)
                {
                    if (isset($seenIds[$attachment->Id]))
                    {
                        continue;
                    }
                    $seenIds[$attachment->Id] = true;
                    $array[] = $this->createButtonProperty(
                        $theah->game,
                        sprintf($theah->game->translate('Discard %s'), $attachment->Name),
                        'discard_' . $attachment->Id
                    );
                }
            }
        }

        return $array;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} have more than one Weapon, Armor, Attire, or Offhand on a character and must choose one to discard: ');
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        $attachmentId = (int) explode('_', $reactionId)[1];
        $attachment = $game->theah->getCardById($attachmentId);
        if ( ! $attachment instanceof Attachment)
        {
            throw new UserException($game->translate("Selection is not an attachment."));
        }

        $character = $game->theah->getCharacterById($attachment->AttachedToId);
        if ( ! $character instanceof Character)
        {
            throw new UserException($game->translate("Attachment is not equipped to a character."));
        }

        if ( ! $this->isValidDiscardChoice($game->theah, $character, $attachment))
        {
            throw new UserException($game->translate("That attachment is not a valid discard choice."));
        }

        $unequipEvent = EventFactory::createAttachmentUnequippedEvent($character->ControllerId, $character->Id, $attachment->Id);
        $game->theah->queueEvent($unequipEvent);

        if ($attachment instanceof CityAttachment)
        {
            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($character->ControllerId, $attachment->Id, $attachment->Location, $character->Id);
        }
        else
        {
            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location, $character->Id);
        }
        $game->theah->queueEvent($discardEvent);

        $game->gamestate->nextState("done");
    }

    private function isValidDiscardChoice(Theah $theah, Character $character, Attachment $attachment): bool
    {
        foreach (self::LIMITED_TYPES as $type)
        {
            $attachments = $this->getLimitedAttachmentsOfType($theah, $character, $type);
            if (count($attachments) > 1 && $attachment->hasTrait($type) && ! $attachment->OffHand)
            {
                return true;
            }
        }

        $offHands = $this->getOffHandAttachments($theah, $character);
        if (count($offHands) > 1 && $attachment->OffHand)
        {
            return true;
        }

        return false;
    }

    private function characterExceedsAttachmentLimit(Theah $theah, Character $character): bool
    {
        foreach (self::LIMITED_TYPES as $type)
        {
            if (count($this->getLimitedAttachmentsOfType($theah, $character, $type)) > 1)
            {
                return true;
            }
        }

        return count($this->getOffHandAttachments($theah, $character)) > 1;
    }

    /**
     * Non-OffHand attachments of the given type.
     * WHY: Offhand text — OffHand does not count against the one Weapon/Armor/Attire limit.
     * OffHand has its own separate limit (see getOffHandAttachments).
     */
    private function getLimitedAttachmentsOfType(Theah $theah, Character $character, string $type): array
    {
        $attachments = [];
        foreach ($character->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment instanceof Attachment && $attachment->hasTrait($type) && ! $attachment->OffHand)
            {
                $attachments[] = $attachment;
            }
        }
        return $attachments;
    }

    private function getOffHandAttachments(Theah $theah, Character $character): array
    {
        $attachments = [];
        foreach ($character->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment instanceof Attachment && $attachment->OffHand)
            {
                $attachments[] = $attachment;
            }
        }
        return $attachments;
    }
}
