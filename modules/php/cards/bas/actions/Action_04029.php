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

        // WHY: Risk leaves hand when played — reserve its wealth (and play cost) before checking equip affordability.
        $selfWealth = $owner->hasTrait("Wealth") ? 2 : 1;

        return -($selfWealth + $owner->getWealthCost());
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

        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        foreach ($opposing as $host)
        {
            foreach ($host->Attachments as $attachmentId)
            {
                $attachment = $theah->getAttachmentById($attachmentId);
                if ($attachment === null)
                {
                    continue;
                }

                [$discount, $explanations] = $theah->getEquipDiscount($performer, $attachment);
                $cost = $attachment->WealthCost - $discount;
                if ($cost < 0)
                {
                    $cost = 0;
                }

                // WHY: 0-cost equips (e.g. Langschwert) stay legal once the risk is in hand — no wealth discard required.
                if ($cost > 0 && $handWealth < $cost)
                {
                    continue;
                }

                [$hasRestrictions, $restrictionExplanation] = $theah->game->hasEquipRestrictions($performer, $attachment);
                if ($hasRestrictions || ! $attachment->canAttachTo($performer))
                {
                    continue;
                }

                $stealable[] = ["attachment" => $attachment, "host" => $host];
            }
        }

        return $stealable;
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
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->getCardObjectFromDb($attachmentId);
            $args["cardId"] = $attachmentId;
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

            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($host->ControllerId, $attachment->Id, $attachment->Location, $owner->Id);
            $game->theah->queueEvent($discardEvent);

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);
            $game->globals->set(Game::CHOSEN_CARD_COST, $attachment->WealthCost);

            $removedFromDiscardEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($host->ControllerId, $attachment->Id);
            $game->theah->queueEvent($removedFromDiscardEvent);

            $addedToHandEvent = EventFactory::createCardAddedToHandEvent($performer->ControllerId, $attachment->Id);
            $game->theah->queueEvent($addedToHandEvent);

            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $attachment->Id, Game::PAY_STATE_EQUIP_ATTACHMENT);
            $game->theah->queueEvent($payEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04029_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("attachmentChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04029_2)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);

            if ($attachment === null || $attachment->Location != Game::LOCATION_HAND || $attachment->ControllerId != $performer->ControllerId)
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

            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $performer->Id);

            $owner = $this->getOwningCard($game->theah);
            $musterEvent = EventFactory::createAttachmentEquippedEvent(
                $performer->ControllerId,
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

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
