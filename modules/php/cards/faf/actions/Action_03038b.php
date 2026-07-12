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

class Action_03038b extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Equipped Character, Destroy Attachment to Draw");
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

        return count($this->getEligibleMovers($theah, $owner)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03038b", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $owner = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03038b)
        {
            $args['performerId'] = $owner->Id;
            $movers = $this->getEligibleMovers($game->theah, $owner);
            $args['ids'] = array_values(array_map(fn(Character $c) => $c->Id, $movers));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03038b_2)
        {
            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);

            $args['performerId'] = $owner->Id;
            $args['characterId'] = $characterId;

            $attachments = [];
            if ($character !== null)
            {
                foreach ($this->getDestroyableAttachments($game->theah, $character) as $attachment)
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

        $owner = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03038b)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Invalid character."));
            }

            if (! $this->isEligibleMover($game->theah, $owner, $character))
            {
                throw new UserException($game->translate("Choose one of your equipped characters not already at Damya's location."));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            // WHY engage=false: City Action prints no Engage cost; move is relocation only.
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $character->Id,
                $character->Location,
                $owner->Location,
                $engage = false,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($moveEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03038b_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("characterChosen");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03038b_2)
        {
            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null || $attachment->FakeAttachment)
            {
                throw new UserException($game->translate("Invalid attachment."));
            }

            if (! in_array($attachment->Id, $character->Attachments))
            {
                throw new UserException($game->translate("Attachment is not equipped to the chosen character."));
            }

            // Capture printed cost before destroy — attachment leaves play afterward.
            $drawCount = $attachment->WealthCost + 1;

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                $attachment->ControllerId,
                $attachment->AttachedToId,
                $attachment->Id
            );
            $game->theah->eventCheck($unequipEvent);
            $game->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent(
                $attachment->OwnerId,
                $attachment->Id,
                $attachment->Location,
                $owner->Id,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            for ($i = 0; $i < $drawCount; $i++)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($drawEvent);
            }

            $game->globals->set(Game::CHOSEN_TARGET, null);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("attachmentDestroyed");
            return;
        }
    }

    /**
     * Your characters with at least one real attachment, not already at Damya's location.
     */
    private function getEligibleMovers(Theah $theah, Character $owner): array
    {
        $characters = $theah->getCharactersInPlayByPlayerId($owner->ControllerId);
        return array_values(array_filter(
            $characters,
            fn(Character $character) => $this->isEligibleMover($theah, $owner, $character)
        ));
    }

    private function isEligibleMover(Theah $theah, Character $owner, Character $character): bool
    {
        if ($character->ControllerId != $owner->ControllerId)
        {
            return false;
        }

        if ($character->Location == $owner->Location)
        {
            return false;
        }

        return count($this->getDestroyableAttachments($theah, $character)) > 0;
    }

    /**
     * @return Attachment[]
     */
    private function getDestroyableAttachments(Theah $theah, Character $character): array
    {
        $attachments = [];
        foreach ($character->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment === null || $attachment->FakeAttachment)
            {
                continue;
            }
            $attachments[] = $attachment;
        }
        return $attachments;
    }
}
