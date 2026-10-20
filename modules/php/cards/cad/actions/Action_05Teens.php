<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Smash and Grab: Covert Action - Engage your performer . Destroy target opposing character's
// equipped attachment. You may take control of it and equip it to your character paying all costs.
//
// Flow (4 GameStates, ids 405019 + 2/3/4):
//   05Teens    pick target opposing character (board highlight)
//   05Teens_2  pick one of its attachments (name buttons) -> EventCharacterTargeted hook
//   05Teens_3  after the destroy resolved: pick your character to equip it to, or Pass
//   05Teens_4  pay the equip cost from hand, then equip
class Action_05Teens extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD - plain strings, no clienttranslate (hide from translators).
        $this->Name = "Covert: Engage; Destroy Equipped Attachment; May Take Control";
        $this->RequiresPerformerSelected = true;
    }

    /**
     * WHY real attachments only: a FakeAttachment (RiskAttachment) is a Risk card posing as an
     * attachment; unequip + discard-from-play does not route it correctly (same discipline as
     * Action_04040 / Action_03072).
     *
     * @return list<Attachment>
     */
    private function getRealAttachments(Theah $theah, Character $character): array
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

    /**
     * "Opposing" = different controller AND same location as the performer.
     *
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        return array_values(array_filter(
            $opposing,
            fn(Character $character) => count($this->getRealAttachments($theah, $character)) > 0
        ));
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        // WHY not RiskCityAction: the heading says "Covert Action", not "City Action". The City
        // requirement comes from Covert (uncontrolled City location), checked explicitly below.
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah)
            {
                // Engage your performer is a cost.
                if ($performer->Engaged || ! $theah->cardInCity($performer))
                {
                    return false;
                }

                // WHY: Covert abilities may only be used at uncontrolled locations (Valeri / Overcoat).
                if ($theah->getCityLocation($performer->Location)->isControlled())
                {
                    return false;
                }

                return count($this->getValidTargets($theah, $performer)) > 0;
            }
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, "Performer not found"];
        }

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, "Target must be controlled by an opponent."];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, "Target must be at your performer's location."];
        }

        if (count($this->getRealAttachments($game->theah, $character)) == 0)
        {
            return [false, "Target must have at least one equipped attachment."];
        }

        return [true, ""];
    }

    // WHY: "Engage your performer" is printed before the bullet, so it is a cost. Pay it at
    // announce (like Rage `_05Hulk`) so a later cancel still paid it. Consequence: no Back button
    // on any of our states - the engage is irreversible.
    public function announceAction(Game $game): void
    {
        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer !== null && ! $performer->Engaged)
        {
            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id);
            $game->theah->queueEvent($engageEvent);
        }

        parent::announceAction($game);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "05Teens", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Destroy only when the targeting hook survives. Unyielding Loyalty / Maryam cancel
        // EventCharacterTargeted; queuing the destroy beside the cancel hook would apply it
        // anyway (same trap documented on Action_04040).
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $target = $event->theah->getCharacterById($event->targetId);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $event->theah->getAttachmentById($attachmentId);

            if ($owner === null || $target === null || $attachment === null || $attachment->FakeAttachment)
            {
                return;
            }

            if ($attachment->AttachedToId != $target->Id)
            {
                return;
            }

            $batchId = $event->batchId ?? $game->getNextEventBatchId();

            // WHY unequip first: the discard events only move the card, they do not detach it.
            $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                $attachment->ControllerId,
                $target->Id,
                $attachment->Id
            );
            $unequipEvent->batchId = $batchId;
            $event->theah->eventCheck($unequipEvent);
            $event->theah->queueEvent($unequipEvent);

            // WHY a real destroy (not the Robbery staging used by `_04029`): the text says
            // "Destroy ... You may take control of it", so the card really lands in a discard
            // pile (destroy triggers fire) and the take-control step pulls it back out.
            // CityAttachments route to the City Discard inside this factory call.
            $discardEvent = EventFactory::createAttachmentDiscardedFromPlayEvent($attachment, $owner->Id, $asEffect = true);
            $discardEvent->batchId = $batchId;
            $event->theah->queueEvent($discardEvent);

            // WHY queued after the destroy: FIFO, so the unequip + discard have resolved (card
            // is in a discard pile) by the time the take-control chooser reads it.
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Teens_3", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    /**
     * Where the destroyed attachment now sits. null = it is not in a pile we can take it from
     * (destroy was prevented, or something sent it elsewhere).
     *
     * @return array{city: bool, playerId: int}|null
     */
    private function getDiscardPileOf(Game $game, Attachment $attachment): ?array
    {
        if ($attachment->Location == Game::LOCATION_CITY_DISCARD)
        {
            return ["city" => true, "playerId" => 0];
        }

        if ($attachment->OwnerId != 0 && $attachment->Location == $game->getPlayerDiscardDeckName($attachment->OwnerId))
        {
            return ["city" => false, "playerId" => (int)$attachment->OwnerId];
        }

        return null;
    }

    /**
     * Your characters that can legally take the attachment and that you can pay for.
     * The Risk already left the hand when this runs, so no wealth reservation is needed.
     *
     * @return list<Character>
     */
    private function getEquipCandidates(Game $game, Attachment $attachment, int $playerId): array
    {
        if ($this->getDiscardPileOf($game, $attachment) === null)
        {
            return [];
        }

        $handWealth = $game->handWealthCount($playerId);
        $candidates = [];
        foreach ($game->theah->getCharactersInPlayByPlayerId($playerId) as $character)
        {
            [$discount, $explanations] = $game->theah->getEquipDiscount($character, $attachment);
            $cost = max(0, $attachment->WealthCost - $discount);
            if ($cost > $handWealth)
            {
                continue;
            }

            [$hasRestrictions, $restrictionExplanation] = $game->hasEquipRestrictions($character, $attachment);
            if ($hasRestrictions || ! $attachment->canAttachTo($character))
            {
                continue;
            }

            $candidates[] = $character;
        }

        return $candidates;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS_2)
        {
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $args["performerId"] = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $args["characterId"] = $targetId;
            $args["attachments"] = [];

            if ($target !== null)
            {
                foreach ($this->getRealAttachments($game->theah, $target) as $attachment)
                {
                    $args["attachments"][] = [
                        "id" => $attachment->Id,
                        "name" => $attachment->Name,
                    ];
                }
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3)
        {
            $owner = $this->getOwningCard($game->theah);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);

            $args["attachmentId"] = $attachmentId;
            $args["ids"] = $attachment !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getEquipCandidates($game, $attachment, $owner->ControllerId))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS_4)
        {
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->getCardObjectFromDb($attachmentId);
            $args["cardId"] = $attachmentId;
            $args["characterId"] = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $args["cost"] = $game->globals->get(Game::CHOSEN_CARD_COST);
            $args["discount"] = $game->globals->get(Game::DISCOUNT);
            $args["card"] = $attachment->getPropertyArray($game);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException("Character not found");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", '${player_name} uses ${card_inject_code} targeting ${character_inject_code}.', [
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code" => $owner->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS_2)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null || $attachment->FakeAttachment)
            {
                throw new UserException("Attachment not found");
            }

            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null)
            {
                throw new UserException("Character not found");
            }

            if ($attachment->AttachedToId != $target->Id)
            {
                throw new UserException("Attachment is not equipped to the target.");
            }

            $owner = $this->getOwningCard($game->theah);

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);

            // WHY: Cancel hook first. The destroy is emitted from handleEvent only when this
            // EventCharacterTargeted survives. Shared batchId lets a canceller strip siblings.
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("attachmentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3)
        {
            $owner = $this->getOwningCard($game->theah);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            if ($attachment === null)
            {
                throw new UserException("Attachment not found");
            }

            $pile = $this->getDiscardPileOf($game, $attachment);
            if ($pile === null)
            {
                throw new UserException("The destroyed attachment is no longer in a discard pile.");
            }

            $candidateIds = array_map(
                fn(Character $character) => $character->Id,
                $this->getEquipCandidates($game, $attachment, $owner->ControllerId)
            );
            if (! in_array($id, $candidateIds, true))
            {
                throw new UserException("That character cannot take control of this attachment (restriction or not enough Wealth).");
            }

            $character = $game->theah->getCharacterById($id);

            // WHY: The equip discount (Theah::calculateInHandPayDiscount for PAY_STATE_EQUIP_ATTACHMENT)
            // and the equip target both read CHOSEN_PERFORMER. The Risk's performer has already paid
            // its Engage at announce, so re-pointing it at the equipping character is safe.
            $game->globals->set(Game::CHOSEN_PERFORMER, $character->Id);
            $game->globals->set(Game::CHOSEN_CARD_COST, $attachment->WealthCost);

            $attachment->ControllerId = $character->ControllerId;
            $game->updateCardObjectInDb($attachment);
            $game->theah->addCardToWorld($attachment);

            $game->notify->all("message", '${card_inject_code}: ${player_name} takes control of ${attachment_inject_code}.', [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "attachment_inject_code" => $attachment->getInjectCode(),
            ]);

            if ($pile["city"])
            {
                $removedEvent = EventFactory::createCardRemovedFromCityDiscardPileEvent($character->ControllerId, $attachment->Id);
            }
            else
            {
                $removedEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($pile["playerId"], $attachment->Id);
            }
            $game->theah->queueEvent($removedEvent);

            $addedToHandEvent = EventFactory::createCardAddedToHandEvent($character->ControllerId, $attachment->Id);
            $game->theah->queueEvent($addedToHandEvent);

            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $attachment->Id, Game::PAY_STATE_EQUIP_ATTACHMENT);
            $game->theah->queueEvent($payEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Teens_4", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3)
        {
            $owner = $this->getOwningCard($game->theah);

            // WHY: "You may" - Pass is always legal, even when a character could take it.
            $game->notify->all("message", '${card_inject_code}: ${player_name} does not take control of the attachment.', [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05TEENS_4)
        {
            $owner = $this->getOwningCard($game->theah);
            $characterId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $character = $game->theah->getCharacterById($characterId);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);

            if ($character === null || $attachment === null)
            {
                throw new UserException("Card not found");
            }

            if ($attachment->Location != Game::LOCATION_HAND || $attachment->ControllerId != $character->ControllerId)
            {
                throw new UserException("Attachment is not in your hand");
            }

            $discount = (int)$game->globals->get(Game::DISCOUNT, 0);
            $cost = (int)$game->globals->get(Game::CHOSEN_CARD_COST);
            $explanations = $game->globals->get(Game::DISCOUNT_EXPLAINATIONS, '');
            $cost -= $discount;
            if ($cost < 0)
            {
                $cost = 0;
            }

            $totalWealth = 0;
            $hasWealthCard = false;
            foreach ($ids as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card === null)
                {
                    throw new UserException(sprintf("Card #%d not found.", $cardId));
                }

                $isWealth = $card->hasTrait("Wealth");
                if ($isWealth)
                {
                    $hasWealthCard = true;
                }
                $totalWealth += $isWealth ? 2 : 1;
            }

            if (! $game->isValidWealthPayment($totalWealth, $cost, $hasWealthCard))
            {
                throw new UserException(sprintf("Cost of Attachment is %d. You selected %d Wealth of cards.", $cost, $totalWealth));
            }

            foreach ($ids as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = true);
                $game->theah->queueEvent($event);
            }

            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $character->Id);

            $equipEvent = EventFactory::createAttachmentEquippedEvent(
                $character->ControllerId,
                $actualTargetId,
                $attachment->Id,
                $discount,
                $cost,
                $asAction = false,
                $explanations,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($equipEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
