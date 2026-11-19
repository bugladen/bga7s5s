<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04029 extends RiskAction implements IAbilityThatTargetsCards
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Take Control of Opposing Attachment and Equip");
        $this->RequiresPerformerSelected = true;
    }

    private function getWealthAdjustment(Theah $theah): ?int
    {
        $owner = $this->getOwningCard($theah);
        if (! $owner instanceof IWealthCost)
        {
            return null;
        }

        // WHY: Only while the Risk is still in hand. After play it is already gone from
        // handWealthCount — applying the reserve again undercounts and empties the chooser
        // (e.g. one payment card left → soft-lock with no attachment buttons).
        // Robbery (Action_01113) only adjusts in isAvailableToPlayer for the same reason.
        if ($owner->Location != Game::LOCATION_HAND)
        {
            return null;
        }

        // WHY: Risk leaves hand when played — reserve its wealth (and play cost) before checking equip affordability.
        $selfWealth = $owner->hasTrait("Wealth") ? 2 : 1;

        return -($selfWealth + $owner->getWealthCost());
    }

    /**
     * Printed "equip it to your character at this location" — any controlled character at the
     * performer's City location may receive the stolen attachment, not only the Merchant.
     *
     * @return list<Character>
     */
    private function getCharactersAtPerformerLocation(Theah $theah, Character $performer): array
    {
        return $theah->getCharactersAtLocationByPlayerId($performer->Location, $performer->ControllerId);
    }

    private function canCharacterEquipAttachment(Theah $theah, Character $character, Attachment $attachment, int $handWealth): bool
    {
        [$discount, $explanations] = $theah->getEquipDiscount($character, $attachment);
        $cost = $attachment->WealthCost - $discount;
        if ($cost < 0)
        {
            $cost = 0;
        }

        // WHY: 0-cost equips (e.g. Langschwert) stay legal once the risk is in hand — no wealth discard required.
        if ($cost > 0 && $handWealth < $cost)
        {
            return false;
        }

        [$hasRestrictions, $restrictionExplanation] = $theah->game->hasEquipRestrictions($character, $attachment);
        if ($hasRestrictions || ! $attachment->canAttachTo($character))
        {
            return false;
        }

        return true;
    }

    /**
     * @return list<array{attachment: Attachment, host: Character}>
     */
    private function getStealableAttachments(Theah $theah, Character $performer, ?int $wealthAdjustment = null): array
    {
        $stealable = [];
        $handWealth = $theah->game->handWealthCount($performer->ControllerId);
        if ($wealthAdjustment !== null)
        {
            $handWealth += $wealthAdjustment;
        }

        $allies = $this->getCharactersAtPerformerLocation($theah, $performer);
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        foreach ($opposing as $host)
        {
            foreach ($host->Attachments as $attachmentId)
            {
                $attachment = $theah->getAttachmentById($attachmentId);
                if ($attachment === null || ! $attachment->canBeMoved())
                {
                    continue;
                }

                // WHY: Printed equip target is any of yours at the location — an attachment is
                // legal to steal if at least one ally there can attach and afford it (not only
                // the Merchant performer). Discount/restriction checks are per candidate host.
                $canEquipSomewhere = false;
                foreach ($allies as $ally)
                {
                    if ($this->canCharacterEquipAttachment($theah, $ally, $attachment, $handWealth))
                    {
                        $canEquipSomewhere = true;
                        break;
                    }
                }

                if (! $canEquipSomewhere)
                {
                    continue;
                }

                $stealable[] = ["attachment" => $attachment, "host" => $host];
            }
        }

        return $stealable;
    }

    /**
     * After the steal: your characters at the Merchant's location who can still equip it.
     *
     * @return list<Character>
     */
    private function getEquipCandidates(Theah $theah, Character $performer, Attachment $attachment): array
    {
        $handWealth = $theah->game->handWealthCount($performer->ControllerId);
        $candidates = [];
        foreach ($this->getCharactersAtPerformerLocation($theah, $performer) as $character)
        {
            if ($this->canCharacterEquipAttachment($theah, $character, $attachment, $handWealth))
            {
                $candidates[] = $character;
            }
        }

        return $candidates;
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $wealthAdjustment = $this->getWealthAdjustment($theah);

        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah, $wealthAdjustment)
            {
                // WHY En Garde Merchant Action: En Garde = precondition (not Engage cost);
                // Merchant = mechanical trait gate (not Sorcerer).
                if ($performer->Engaged || ! $performer->hasTrait("Merchant"))
                {
                    return false;
                }

                // WHY: Printed "at this location" implies a City location — not Home.
                if (! $theah->cardInCity($performer))
                {
                    return false;
                }

                return count($this->getStealableAttachments($theah, $performer, $wealthAdjustment)) > 0;
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

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04029", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["attachments"] = [];

            if ($performer !== null)
            {
                foreach ($this->getStealableAttachments($game->theah, $performer, $this->getWealthAdjustment($game->theah)) as $entry)
                {
                    $attachment = $entry["attachment"];
                    $host = $entry["host"];
                    $args["attachments"][] = [
                        "id" => $attachment->Id,
                        "name" => $attachment->Name . " (" . $host->Name . ")",
                    ];
                }
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029_2)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);

            $args["performerId"] = $performerId;
            $args["attachmentId"] = $attachmentId;
            $args["ids"] = [];

            if ($performer !== null && $attachment !== null)
            {
                $args["ids"] = array_map(
                    fn(Character $character) => $character->Id,
                    $this->getEquipCandidates($game->theah, $performer, $attachment)
                );
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029_3)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null)
            {
                throw new UserException($game->translate("Attachment not found"));
            }

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            if ($performer->Engaged || ! $performer->hasTrait("Merchant"))
            {
                throw new UserException($game->translate("Performer must be an en garde Merchant"));
            }

            if (! $game->theah->cardInCity($performer))
            {
                throw new UserException($game->translate("Performer must be at a City location"));
            }

            $host = $game->theah->getCharacterById($attachment->AttachedToId);
            if ($host === null)
            {
                throw new UserException($game->translate("Attachment is not equipped to a character"));
            }

            if ($host->ControllerId == $performer->ControllerId || $host->Location != $performer->Location)
            {
                throw new UserException($game->translate("Attachment must be equipped to an opposing character at this location"));
            }

            $isStealable = false;
            foreach ($this->getStealableAttachments($game->theah, $performer, $this->getWealthAdjustment($game->theah)) as $entry)
            {
                if ($entry["attachment"]->Id == $attachment->Id)
                {
                    $isStealable = true;
                    break;
                }
            }

            if (! $isStealable)
            {
                throw new UserException($game->translate("You cannot take control of this attachment"));
            }

            $owner = $this->getOwningCard($game->theah);
            $game->globals->set(Game::CHOSEN_OPPONENT, $host->ControllerId);

            $attachment->ControllerId = $performer->ControllerId;
            $game->updateCardObjectInDb($attachment);
            $game->theah->addCardToWorld($attachment);

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($host->ControllerId, $host->Id, $attachment->Id);
            $game->theah->queueEvent($unequipEvent);

            // WHY not createAttachmentDiscardedFromPlayEvent: steal-to-hand stages through
            // player discard then createCardRemovedFromPlayerDiscardPileEvent. City routing
            // would break that remove step and fire Eager Blade → Bazaar mid-steal.
            // Same staging as Maneuver_01113.
            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($host->ControllerId, $attachment->Id, $attachment->Location, $owner->Id);
            $game->theah->queueEvent($discardEvent);

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);
            $game->globals->set(Game::CHOSEN_CARD_COST, $attachment->WealthCost);

            $removedFromDiscardEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($host->ControllerId, $attachment->Id);
            $game->theah->queueEvent($removedFromDiscardEvent);

            $addedToHandEvent = EventFactory::createCardAddedToHandEvent($performer->ControllerId, $attachment->Id);
            $game->theah->queueEvent($addedToHandEvent);

            // WHY: Equip host is chosen next — pay/discount must use that character, not the Merchant
            // performer. Defer PAY_STATE_EQUIP_ATTACHMENT until after the location host is locked.
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04029_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("attachmentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029_2)
        {
            $merchantId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $merchant = $game->theah->getCharacterById($merchantId);
            if ($merchant === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            if ($attachment === null)
            {
                throw new UserException($game->translate("Attachment not found"));
            }

            $candidateIds = array_map(
                fn(Character $character) => $character->Id,
                $this->getEquipCandidates($game->theah, $merchant, $attachment)
            );
            if (! in_array($id, $candidateIds, true))
            {
                throw new UserException($game->translate("That character cannot equip this attachment"));
            }

            $character = $game->theah->getCharacterById($id);

            // WHY: PAY_STATE_EQUIP_ATTACHMENT discount + equip target both read CHOSEN_PERFORMER.
            // Merchant Action already spent its En Garde gate; re-point to the receiving character
            // (same as Smash and Grab Action_05Teens).
            $game->globals->set(Game::CHOSEN_PERFORMER, $character->Id);

            $owner = $this->getOwningCard($game->theah);
            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $attachment->Id, Game::PAY_STATE_EQUIP_ATTACHMENT);
            $game->theah->queueEvent($payEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04029_3", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        // WHY: Escape hatch for empty chooser (stale wealth double-count soft-locks, or
        // every stealable attachment became illegal after play). Not a voluntary Pass
        // when options remain — Forced-style mandatory when fulfillable.
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $stealable = $performer === null
                ? []
                : $this->getStealableAttachments($game->theah, $performer, $this->getWealthAdjustment($game->theah));

            if (count($stealable) > 0)
            {
                throw new UserException($game->translate("You must choose an attachment to take control of"));
            }

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${card_inject_code}: No legal attachments to take control of. The Action ends.'), [
                "card_inject_code" => $owner->getInjectCode(),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029_2)
        {
            $merchantId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $merchant = $game->theah->getCharacterById($merchantId);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            $candidates = ($merchant === null || $attachment === null)
                ? []
                : $this->getEquipCandidates($game->theah, $merchant, $attachment);

            if (count($candidates) > 0)
            {
                throw new UserException($game->translate("You must choose a character to equip the attachment to"));
            }

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${card_inject_code}: No legal characters to equip to. The Action ends.'), [
                "card_inject_code" => $owner->getInjectCode(),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029_3)
        {
            $characterId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $character = $game->theah->getCharacterById($characterId);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);

            if ($character === null || $attachment === null || $attachment->Location != Game::LOCATION_HAND || $attachment->ControllerId != $character->ControllerId)
            {
                throw new UserException($game->translate("Attachment is not in your hand"));
            }

            $discount = $game->globals->get(Game::DISCOUNT);
            $cost = $game->globals->get(Game::CHOSEN_CARD_COST);
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
                    throw new UserException(sprintf($game->translate("Card #%d not found."), $cardId));
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
                throw new UserException(sprintf($game->translate("Cost of Attachment is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
            }

            foreach ($ids as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = true);
                $game->theah->queueEvent($event);
            }

            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $character->Id);

            $owner = $this->getOwningCard($game->theah);
            $musterEvent = EventFactory::createAttachmentEquippedEvent(
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
            $game->theah->queueEvent($musterEvent);

            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);
            if ($leader === null || ! $leader->hasTrait("Villain"))
            {
                $lastControllerId = (int)$game->globals->get(Game::CHOSEN_OPPONENT);
                $drawEvent = EventFactory::createCardDrawnEvent(
                    $lastControllerId,
                    sprintf($game->translate("%s effect"), $owner->getInjectCode())
                );
                $game->theah->queueEvent($drawEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($character->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
