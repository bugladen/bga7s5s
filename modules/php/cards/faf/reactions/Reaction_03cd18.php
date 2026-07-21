<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03cd18 extends CardReaction
{
    // '' (idle), 'choose', 'searchA', 'moveB', 'destroyB'
    private string $stage = '';
    private string $chosenLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Search deck for an attachment, or move and destroy");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        switch ($this->stage)
        {
            case 'choose':
                return $base . $theah->game->translate('${you} must choose one effect:');
            case 'searchA':
                return $base . $theah->game->translate('${you} must choose an attachment from your deck to reveal and add to your hand:');
            case 'moveB':
                return $base . $theah->game->translate('${you} must choose a location to move to (must have an opposing character with an attachment):');
            case 'destroyB':
                return $base . $theah->game->translate('${you} must choose an attachment to destroy:');
        }
        return $base;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $game = $theah->game;
        $owner = $this->getOwningCharacter($theah);

        switch ($this->stage)
        {
            case 'choose':
                if ($this->hasAttachmentInDeck($game, $owner->ControllerId))
                {
                    $array[] = $this->createButtonProperty($game, $game->translate('Search deck for an attachment'), 'optionA');
                }
                if ($this->hasMoveAndDestroyTarget($theah, $owner))
                {
                    $array[] = $this->createButtonProperty($game, $game->translate('Move and destroy an opposing attachment'), 'optionB');
                }
                break;

            case 'searchA':
                $array[] = $this->createButtonProperty($game, $game->translate('< Back'), 'back');
                // Dedupe by Name: multiple copies of the same attachment are
                // indistinguishable to the player; show one button per unique name.
                $seen = [];
                foreach ($this->getAttachmentsInDeck($game, $owner->ControllerId) as $card)
                {
                    if (isset($seen[$card->Name]))
                    {
                        continue;
                    }
                    $seen[$card->Name] = true;
                    $array[] = $this->createButtonProperty($game, $card->Name, "searchA-{$card->Id}");
                }
                break;

            case 'moveB':
                $array[] = $this->createButtonProperty($game, $game->translate('< Back'), 'back');
                foreach ($this->getValidDestinations($theah, $owner) as $location)
                {
                    $array[] = $this->createButtonProperty($game, $location, "moveB-{$location}");
                }
                break;

            case 'destroyB':
                // No `< Back`: the move event has already committed by the time we reach
                // this stage, so we can't rewind to the previous location choice.
                foreach ($this->getOpposingAttachmentsAt($theah, $owner, $owner->Location) as $attachment)
                {
                    $character = $attachment->attachedTo($theah);
                    $label = $character !== null
                        ? sprintf('%s (%s)', $attachment->Name, $character->Name)
                        : $attachment->Name;
                    $array[] = $this->createButtonProperty($game, $label, "destroyB-{$attachment->Id}");
                }
                break;
        }

        $array[] = $this->createButtonProperty($game, $game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: only "After you recruit Kalla and Adelheide" triggers this reaction.
        // EventCharacterRecruited fires after the hub sets ControllerId on Kalla
        // (runEventHubAfterCards defaults to false). Recruitment does not change
        // Location, so cardInCity($owner) reading $owner->Location is authoritative.
        if ($event instanceof EventCharacterRecruited && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($event->characterId == $owner->Id
                && $event->theah->cardInCity($owner)
                && $this->hasAnyValidOption($event->theah, $owner))
            {
                $this->stage = 'choose';
                $this->chosenLocation = '';
                $owner->IsUpdated = true;

                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId === 'decline')
        {
            $this->resetStage();
            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'back')
        {
            // Only valid in searchA / moveB — both rewind to choose.
            if ($this->stage === 'searchA' || $this->stage === 'moveB')
            {
                $this->stage = 'choose';
                $this->chosenLocation = '';
            }
            $owner->IsUpdated = true;
            $this->requeue($game, $owner->ControllerId, $owner->Id);
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'optionA')
        {
            $this->stage = 'searchA';
            $owner->IsUpdated = true;
            $this->requeue($game, $owner->ControllerId, $owner->Id);
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'optionB')
        {
            $this->stage = 'moveB';
            $owner->IsUpdated = true;
            $this->requeue($game, $owner->ControllerId, $owner->Id);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'searchA-'))
        {
            $cardId = (int)substr($reactionId, strlen('searchA-'));
            $this->resolveSearch($game, $owner, $cardId);
            $this->resetStage();
            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'moveB-'))
        {
            $location = substr($reactionId, strlen('moveB-'));
            $this->chosenLocation = $location;
            $this->stage = 'destroyB';
            $owner->IsUpdated = true;

            // Queue the move first, then the reaction transition. The move commits
            // before the transition re-enters playerReaction, so by the time
            // getReactionButtonProperties('destroyB') runs, $owner->Location reflects
            // the new location and "opposing" can be queried against it.
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $location,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($moveEvent);

            $this->requeue($game, $owner->ControllerId, $owner->Id);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'destroyB-'))
        {
            $attachmentId = (int)substr($reactionId, strlen('destroyB-'));
            $this->resolveDestroy($game, $owner, $attachmentId);
            $this->resetStage();
            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        $game->gamestate->nextState("done");
    }

    private function requeue(Game $game, int $playerId, int $sourceId): void
    {
        $transition = EventFactory::createReactionTransitionEvent($playerId, $sourceId, $this->Id);
        $game->theah->queueEvent($transition);
    }

    private function resetStage(): void
    {
        $this->stage = '';
        $this->chosenLocation = '';
    }

    private function hasAnyValidOption(Theah $theah, $owner): bool
    {
        return $this->hasAttachmentInDeck($theah->game, $owner->ControllerId)
            || $this->hasMoveAndDestroyTarget($theah, $owner);
    }

    private function hasAttachmentInDeck(Game $game, int $playerId): bool
    {
        return count($this->getAttachmentsInDeck($game, $playerId)) > 0;
    }

    /**
     * @return Attachment[]
     */
    private function getAttachmentsInDeck(Game $game, int $playerId): array
    {
        $deckName = $game->getPlayerFactionDeckName($playerId);
        $deck = $game->getGameDeckObject()->getCardsInLocation($deckName);
        $attachments = [];
        foreach ($deck as $deckCard)
        {
            $card = $game->getCardObjectFromDb($deckCard['id']);
            if ($card instanceof Attachment)
            {
                $attachments[] = $card;
            }
        }
        return $attachments;
    }

    /**
     * @return string[] city-location names where an opposing-controller character has at least one equipped attachment
     */
    private function getValidDestinations(Theah $theah, $owner): array
    {
        $locations = [];
        foreach (array_keys($theah->getCityLocations()) as $location)
        {
            if (count($this->getOpposingAttachmentsAt($theah, $owner, $location)) > 0)
            {
                $locations[] = $location;
            }
        }
        return $locations;
    }

    private function hasMoveAndDestroyTarget(Theah $theah, $owner): bool
    {
        return count($this->getValidDestinations($theah, $owner)) > 0;
    }

    /**
     * @return Attachment[] attachments equipped to opposing characters at $location
     */
    private function getOpposingAttachmentsAt(Theah $theah, $owner, string $location): array
    {
        $attachments = [];
        // "Opposing" = different controller AND same location as the owner.
        foreach ($theah->getOpposingCharactersAtLocation($location, $owner->ControllerId) as $character)
        {
            foreach ($character->Attachments as $attachmentId)
            {
                $card = $theah->getCardById($attachmentId);
                if ($card instanceof Attachment)
                {
                    $attachments[] = $card;
                }
            }
        }
        return $attachments;
    }

    private function resolveSearch(Game $game, $owner, int $cardId): void
    {
        $card = $game->theah->getCardById($cardId);
        $deckName = $game->getPlayerFactionDeckName($owner->ControllerId);

        if ($card === null || !($card instanceof Attachment) || $card->Location !== $deckName)
        {
            // Stale button click (deck contents changed) — bail without effect.
            return;
        }

        $removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($owner->ControllerId, $card->Id);
        $game->theah->eventCheck($removeEvent);

        $addEvent = EventFactory::createCardAddedToHandEvent($owner->ControllerId, $card->Id);
        $game->theah->eventCheck($addEvent);

        $game->theah->queueEvent($removeEvent);
        $game->theah->queueEvent($addEvent);

        $game->notify->all("message",
            clienttranslate('${card_inject_code}: ${player_name} searched their deck and revealed ${picked_card}, adding it to their hand.'),
            [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "picked_card" => $card->getInjectCode(),
            ]);

        $game->getGameDeckObject()->shuffle($deckName);
        $game->notify->all("message", clienttranslate('${player_name} shuffles their deck.'), [
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
        ]);
    }

    private function resolveDestroy(Game $game, $owner, int $attachmentId): void
    {
        $attachment = $game->theah->getCardById($attachmentId);
        if (!($attachment instanceof Attachment) || !$attachment->isAttached())
        {
            return;
        }

        $character = $attachment->attachedTo($game->theah);
        // Re-verify "opposing" at resolution time — the move may have been intercepted
        // or the attachment may have moved between button render and click.
        if ($character === null
            || $character->ControllerId == $owner->ControllerId
            || $character->Location !== $owner->Location)
        {
            return;
        }

        $unequipEvent = EventFactory::createAttachmentUnequippedEvent($attachment->ControllerId, $attachment->AttachedToId, $attachment->Id);
        $game->theah->eventCheck($unequipEvent);
        $game->theah->queueEvent($unequipEvent);

        $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location, $owner->Id, true);
        $game->theah->queueEvent($discardEvent);

        $game->notify->all("message",
            clienttranslate('${card_inject_code}: ${attachment_inject_code} equipped to ${target_inject_code} is destroyed.'),
            [
                "card_inject_code" => $owner->getInjectCode(),
                "attachment_inject_code" => $attachment->getInjectCode(),
                "target_inject_code" => $character->getInjectCode(),
            ]);
    }
}
