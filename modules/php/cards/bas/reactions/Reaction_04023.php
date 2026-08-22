<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04023 extends CardReaction
{
    // WHY: Multi-stage inside playerReaction (Tomas/Don Constanzo). Monet can move
    // during any EVENTS dispatcher; dedicated chooseList states would need "04023"
    // wired on every dispatcher. Buttons + requeue stay context-agnostic.
    private string $stage = ''; // '', 'equip', 'character', 'pay', 'discard'

    /** @var array<int> revealed card ids still pending discard/sink (not yet equipped) */
    private array $remainingCardIds = [];

    private int $chosenAttachmentId = 0;
    private int $chosenCharacterId = 0;
    private int $paidDiscount = 0;
    private string $paidExplanations = '';
    private int $paidCost = 0;

    /** @var array<int> */
    private array $paidCardIds = [];
    private int $paidWealth = 0;
    private bool $paidHasWealthCard = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Reveal deck; may equip attachment; discard any; sink rest");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->stage === 'pay')
        {
            $attachment = $theah->getAttachmentById($this->chosenAttachmentId);
            return $base . sprintf(
                $theah->game->translate('Pay %d Wealth for %s — click cards in your hand. Paid so far: %d.'),
                $this->paidCost,
                $attachment ? $attachment->Name : '',
                $this->paidWealth
            );
        }

        if ($this->stage === 'character')
        {
            $attachment = $theah->getAttachmentById($this->chosenAttachmentId);
            return $base . sprintf(
                $theah->game->translate('${you} may equip %s to a character you control at this location: '),
                $attachment ? $attachment->Name : ''
            );
        }

        if ($this->stage === 'equip')
        {
            return $base . $theah->game->translate('${you} may equip a revealed attachment, paying all costs: ');
        }

        if ($this->stage === 'discard')
        {
            return $base . $theah->game->translate('${you} may discard any of the remaining revealed cards, then sink the rest: ');
        }

        return $base . $theah->game->translate('${you} may reveal the top four cards of your deck (En Garde): ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return $array;
        }

        if ($this->stage === '')
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Reveal'), 'reveal');
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
            return $array;
        }

        if ($this->stage === 'equip')
        {
            foreach ($this->getEquippableAttachments($theah, $owner) as $attachment)
            {
                $label = sprintf($theah->game->translate('Equip %s'), $attachment->Name);
                $array[] = $this->createButtonProperty($theah->game, $label, 'attach-' . $attachment->Id);
            }
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Skip equip'), 'skipEquip');
            return $array;
        }

        if ($this->stage === 'character')
        {
            $attachment = $this->getRevealedAttachment($theah->game, $theah, $this->chosenAttachmentId);
            if ($attachment instanceof Attachment)
            {
                foreach ($this->getEligibleEquipTargets($theah, $owner, $attachment, true) as $character)
                {
                    $cost = $this->equipCost($theah, $character, $attachment);
                    $label = sprintf(
                        $theah->game->translate('Equip to %s (cost %d)'),
                        $character->Name,
                        $cost
                    );
                    $array[] = $this->createButtonProperty($theah->game, $label, 'equip_' . $character->Id);
                }
            }
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('< Back'), 'back');
            return $array;
        }

        if ($this->stage === 'pay')
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('< Back'), 'back');
            $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            foreach ($hand as $card)
            {
                if (in_array($card->Id, $this->paidCardIds, true))
                {
                    continue;
                }
                if (in_array($card->Id, $this->remainingCardIds, true)
                    || $card->Id == $this->chosenAttachmentId)
                {
                    continue;
                }
                if (! $this->wouldClickProduceValidPayment($card, $this->paidCost))
                {
                    continue;
                }

                $wealth = $card->hasTrait("Wealth") ? 2 : 1;
                $label = sprintf($theah->game->translate('Pay with %s (+%d Wealth)'), $card->Name, $wealth);
                $array[] = $this->createButtonProperty($theah->game, $label, 'pay-' . $card->Id);
            }
            return $array;
        }

        if ($this->stage === 'discard')
        {
            foreach ($this->remainingCardIds as $cardId)
            {
                $card = $theah->game->getCardObjectFromDb($cardId);
                if ($card === null)
                {
                    continue;
                }
                $label = sprintf($theah->game->translate('Discard %s'), $card->Name);
                $array[] = $this->createButtonProperty($theah->game, $label, 'discard-' . $cardId);
            }
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Sink the rest'), 'sinkRest');
            return $array;
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoved))
        {
            return;
        }
        if (! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null)
        {
            return;
        }

        // En Garde Reaction — precondition, not an Engage cost.
        if ($owner->Engaged)
        {
            return;
        }

        // Trigger: Monet herself moves (Reaction_03025 shape).
        if ($event->cardId != $owner->Id)
        {
            return;
        }

        if (! $event->theah->locationInCity($event->toLocation))
        {
            return;
        }

        // Precondition: at least one card to reveal (reshuffle-from-discard included).
        $deckCards = $event->theah->game->getCardsOnTopOfPlayerFactionDeck($owner->ControllerId, 1);
        if (count($deckCards) == 0)
        {
            return;
        }

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'pass')
        {
            // Pass before reveal — do NOT setUsed; reaction stays available.
            $this->resetState($owner);
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'reveal' && $this->stage === '')
        {
            $this->handleReveal($game, $owner);
            return;
        }

        if ($this->stage === 'equip' && str_starts_with($reactionId, 'attach-'))
        {
            $this->handleAttachPick($game, $owner, $reactionId);
            return;
        }

        if ($this->stage === 'equip' && $reactionId === 'skipEquip')
        {
            $this->enterDiscardStage($game, $owner);
            return;
        }

        if ($this->stage === 'character' && str_starts_with($reactionId, 'equip_'))
        {
            $this->handleEquipPick($game, $owner, $reactionId);
            return;
        }

        if (str_starts_with($reactionId, 'pay-'))
        {
            $this->handlePay($game, $owner, $reactionId);
            return;
        }

        if ($reactionId === 'back')
        {
            $this->handleBack($game, $owner);
            return;
        }

        if ($this->stage === 'discard' && str_starts_with($reactionId, 'discard-'))
        {
            $this->handleDiscardOne($game, $owner, $reactionId);
            return;
        }

        if ($this->stage === 'discard' && $reactionId === 'sinkRest')
        {
            $this->finishSink($game, $owner);
            return;
        }

        $game->gamestate->nextState("done");
    }

    private function handleReveal(Game $game, Character $owner): void
    {
        if ($owner->Engaged)
        {
            $game->gamestate->nextState("done");
            return;
        }

        $deckCards = $game->getCardsOnTopOfPlayerFactionDeck($owner->ControllerId, 4);
        $names = [];
        $ids = [];
        $attachmentCount = 0;
        foreach ($deckCards as $deckCard)
        {
            $card = $game->getCardObjectFromDb($deckCard['id']);
            if ($card === null)
            {
                continue;
            }
            $ids[] = (int) $card->Id;
            $names[] = $card->getInjectCode();
            if ($card instanceof Attachment)
            {
                $attachmentCount++;
            }
        }

        $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${player_name} reveals the top cards of their deck. ${count} Attachment(s) found. (${names})'), [
            'reaction_inject_code' => $owner->getInjectCode(),
            'player_name' => $game->getPlayerNameById($owner->ControllerId),
            'count' => $attachmentCount,
            'names' => implode(', ', $names),
        ]);

        $this->remainingCardIds = $ids;
        $this->chosenAttachmentId = 0;
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $this->paidDiscount = 0;
        $this->paidExplanations = '';
        $this->paidCost = 0;

        // Affordable canAttachTo hosts only (user requirement). Skip to discard if none.
        if (count($this->getEquippableAttachments($game->theah, $owner)) == 0)
        {
            $this->stage = 'discard';
        }
        else
        {
            $this->stage = 'equip';
        }

        // WHY: Do NOT setUsed here. Theah skips reaction transitions when
        // !isAvailable() — burning Used before requeue dropped the equip stage
        // entirely (reveal notify fired, then silence). Tomas/Don Constanzo setUsed
        // only when the multi-stage flow finishes.
        $this->persist($game, $owner);
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handleAttachPick(Game $game, Character $owner, string $reactionId): void
    {
        $attachmentId = (int) substr($reactionId, strlen('attach-'));
        $attachment = $this->getRevealedAttachment($game, $game->theah, $attachmentId);
        if (! ($attachment instanceof Attachment)
            || ! in_array($attachmentId, $this->remainingCardIds, true))
        {
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (count($this->getEligibleEquipTargets($game->theah, $owner, $attachment, true)) == 0)
        {
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $this->chosenAttachmentId = $attachmentId;
        $this->stage = 'character';
        $this->persist($game, $owner);
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handleEquipPick(Game $game, Character $owner, string $reactionId): void
    {
        $characterId = (int) substr($reactionId, strlen('equip_'));
        $attachment = $this->getRevealedAttachment($game, $game->theah, $this->chosenAttachmentId);
        if (! ($attachment instanceof Attachment))
        {
            $this->enterDiscardStage($game, $owner);
            return;
        }

        $eligible = $this->getEligibleEquipTargets($game->theah, $owner, $attachment, true);
        $match = null;
        foreach ($eligible as $character)
        {
            if ($character->Id == $characterId)
            {
                $match = $character;
                break;
            }
        }
        if ($match === null)
        {
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        [$discount, $explanations] = $game->theah->getEquipDiscount($match, $attachment);
        $cost = $attachment->WealthCost - $discount;
        if ($cost < 0)
        {
            $cost = 0;
        }

        $handWealth = $game->handWealthCount($owner->ControllerId);
        if ($handWealth < $cost)
        {
            throw new UserException(sprintf(
                $game->translate("You do not have enough Wealth to equip this card (with a discount of %s)."),
                $discount
            ));
        }

        $this->chosenCharacterId = $match->Id;
        $this->paidDiscount = $discount;
        $this->paidExplanations = is_string($explanations) ? $explanations : '';
        $this->paidCost = $cost;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;

        if ($cost <= 0)
        {
            $this->finalizeEquip($game, $owner);
            return;
        }

        $this->stage = 'pay';
        $this->persist($game, $owner);
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handlePay(Game $game, Character $owner, string $reactionId): void
    {
        if ($this->stage !== 'pay')
        {
            $game->gamestate->nextState("done");
            return;
        }

        $cardId = (int) substr($reactionId, strlen('pay-'));
        $card = $game->theah->getCardById($cardId);

        if ($card === null
            || $card->Location !== Game::LOCATION_HAND
            || $card->OwnerId !== $owner->ControllerId
            || in_array($card->Id, $this->paidCardIds, true)
            || $card->Id == $this->chosenAttachmentId)
        {
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (! $this->wouldClickProduceValidPayment($card, $this->paidCost))
        {
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $wealth = $card->hasTrait("Wealth") ? 2 : 1;
        $this->paidCardIds[] = $card->Id;
        $this->paidWealth += $wealth;
        if ($card->hasTrait("Wealth"))
        {
            $this->paidHasWealthCard = true;
        }
        $owner->IsUpdated = true;

        if ($this->isPaymentComplete($this->paidCost))
        {
            $this->finalizeEquip($game, $owner);
            return;
        }

        $this->persist($game, $owner);
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handleBack(Game $game, Character $owner): void
    {
        if ($this->stage === 'pay' && count($this->paidCardIds) > 0)
        {
            array_pop($this->paidCardIds);
            $this->recomputePaidTotals($game);
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if ($this->stage === 'pay')
        {
            $this->stage = 'character';
            $this->chosenCharacterId = 0;
            $this->paidCost = 0;
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if ($this->stage === 'character')
        {
            $this->stage = 'equip';
            $this->chosenAttachmentId = 0;
            $this->chosenCharacterId = 0;
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
        }
    }

    private function finalizeEquip(Game $game, Character $owner): void
    {
        $attachment = $this->getRevealedAttachment($game, $game->theah, $this->chosenAttachmentId);
        $character = $game->theah->getCharacterById($this->chosenCharacterId);
        if (! ($attachment instanceof Attachment) || ! ($character instanceof Character))
        {
            $this->enterDiscardStage($game, $owner);
            return;
        }

        foreach ($this->paidCardIds as $paidCardId)
        {
            $paidCard = $game->theah->getCardById($paidCardId);
            if ($paidCard === null)
            {
                continue;
            }
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($paidCard->OwnerId, $paidCard->Id, $sourceId = 0, $asPayment = true);
            $game->theah->queueEvent($discardEvent);
        }

        $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $character->Id);

        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} equips ${attachment_inject_code} to ${character_inject_code} from the revealed cards.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
            "attachment_inject_code" => $attachment->getInjectCode(),
            "character_inject_code" => $character->getInjectCode(),
        ]);

        $equipEvent = EventFactory::createAttachmentEquippedEvent(
            $owner->ControllerId,
            $actualTargetId,
            $attachment->Id,
            $this->paidDiscount,
            $this->paidCost,
            $asAction = true,
            $this->paidExplanations,
            false,
            $owner->Id,
            $this->Id
        );
        $game->theah->eventCheck($equipEvent);
        $game->theah->queueEvent($equipEvent);

        $this->remainingCardIds = array_values(array_filter(
            $this->remainingCardIds,
            fn($id) => $id != $attachment->Id
        ));
        $this->chosenAttachmentId = 0;
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $this->paidDiscount = 0;
        $this->paidExplanations = '';
        $this->paidCost = 0;

        $this->enterDiscardStage($game, $owner);
    }

    private function enterDiscardStage(Game $game, Character $owner): void
    {
        if (count($this->remainingCardIds) == 0)
        {
            $this->finishReaction($game, $owner);
            return;
        }

        $this->stage = 'discard';
        $this->persist($game, $owner);
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    /** Terminal: persist clear state + burn daily use. */
    private function finishReaction(Game $game, Character $owner): void
    {
        $this->resetState($owner);
        $this->setUsed($game->theah, true);
        $game->gamestate->nextState("done");
    }

    private function handleDiscardOne(Game $game, Character $owner, string $reactionId): void
    {
        $cardId = (int) substr($reactionId, strlen('discard-'));
        if (! in_array($cardId, $this->remainingCardIds, true))
        {
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $card = $game->getCardObjectFromDb($cardId);
        $deckName = $game->getPlayerFactionDeckName($owner->ControllerId);
        $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);

        if ($card === null || $card->Location != $deckName)
        {
            $this->remainingCardIds = array_values(array_filter(
                $this->remainingCardIds,
                fn($id) => $id != $cardId
            ));
            $this->persist($game, $owner);
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        // WHY: No EventFactory for deck→player-discard. Mirror Action_01134 / Action_02002
        // (reveal-top then discard any) — notify + moveCard.
        $game->notify->all("cardAddedToPlayerDiscardPile", clienttranslate('${reaction_inject_code}: ${card_inject_code} has been moved to the discard pile.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "card_inject_code" => $card->getInjectCode(),
            "playerId" => $owner->ControllerId,
            "card" => $card->getPropertyArray($game),
        ]);
        $game->moveCard($cardId, $discardName, 0, $card);

        $this->remainingCardIds = array_values(array_filter(
            $this->remainingCardIds,
            fn($id) => $id != $cardId
        ));
        $owner->IsUpdated = true;

        if (count($this->remainingCardIds) == 0)
        {
            $this->finishReaction($game, $owner);
            return;
        }

        $this->persist($game, $owner);
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function finishSink(Game $game, Character $owner): void
    {
        foreach ($this->remainingCardIds as $cardId)
        {
            $event = EventFactory::createCardAddedToFactionDeckEvent($owner->ControllerId, $cardId, false);
            $game->theah->queueEvent($event);
        }

        $this->finishReaction($game, $owner);
    }

    /**
     * Load a revealed deck card as Attachment. Prefer DB load — faction-deck
     * cards are not in Theah::buildCity's in-world set.
     */
    private function getRevealedAttachment(Game $game, Theah $theah, int $cardId): ?Attachment
    {
        $card = $game->getCardObjectFromDb($cardId);
        if ($card instanceof Attachment)
        {
            return $card;
        }
        return $theah->getAttachmentById($cardId);
    }

    /**
     * @return Attachment[] revealed attachments that canAttachTo ≥1 controlled character here
     */
    private function getEquippableAttachments(Theah $theah, Character $owner): array
    {
        $out = [];
        foreach ($this->remainingCardIds as $cardId)
        {
            $card = $this->getRevealedAttachment($theah->game, $theah, (int) $cardId);
            if (! ($card instanceof Attachment))
            {
                continue;
            }
            // WHY: Wealth-gate the list — only show attachments the player can pay for
            // on at least one legal host at this location.
            if (count($this->getEligibleEquipTargets($theah, $owner, $card, $requireAffordable = true)) == 0)
            {
                continue;
            }
            $out[] = $card;
        }
        return $out;
    }

    /**
     * @return Character[]
     */
    private function getEligibleEquipTargets(Theah $theah, Character $owner, Attachment $attachment, bool $requireAffordable = false): array
    {
        $out = [];
        $handWealth = $theah->game->handWealthCount($owner->ControllerId);
        $characters = $theah->getCharactersAtLocationByPlayerId($owner->Location, $owner->ControllerId);
        foreach ($characters as $character)
        {
            if (! ($character instanceof Character))
            {
                continue;
            }
            if (! $attachment->canAttachTo($character))
            {
                continue;
            }
            [$hasRestrictions] = $theah->game->hasEquipRestrictions($character, $attachment);
            if ($hasRestrictions)
            {
                continue;
            }
            if ($requireAffordable)
            {
                $cost = $this->equipCost($theah, $character, $attachment);
                if ($handWealth < $cost)
                {
                    continue;
                }
            }
            $out[] = $character;
        }
        return $out;
    }

    private function persist(Game $game, Character $owner): void
    {
        $owner->IsUpdated = true;
        $game->updateCardObjectInDb($owner);
    }

    private function equipCost(Theah $theah, Character $performer, Attachment $attachment): int
    {
        [$discount] = $theah->getEquipDiscount($performer, $attachment);
        $cost = $attachment->WealthCost - $discount;
        return $cost < 0 ? 0 : $cost;
    }

    private function requeue(Game $game, Character $owner): void
    {
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($transition);
    }

    private function resetState(?Character $owner): void
    {
        $this->stage = '';
        $this->remainingCardIds = [];
        $this->chosenAttachmentId = 0;
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $this->paidDiscount = 0;
        $this->paidExplanations = '';
        $this->paidCost = 0;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function isPaymentComplete(int $cost): bool
    {
        if ($this->paidWealth == $cost)
        {
            return true;
        }
        if ($this->paidHasWealthCard && $this->paidWealth == $cost + 1)
        {
            return true;
        }
        return false;
    }

    private function wouldClickProduceValidPayment($card, int $cost): bool
    {
        $add = $card->hasTrait("Wealth") ? 2 : 1;
        $newWealth = $this->paidWealth + $add;
        $newHasWealth = $this->paidHasWealthCard || $card->hasTrait("Wealth");

        if ($newWealth < $cost)
        {
            return true;
        }
        if ($newWealth == $cost)
        {
            return true;
        }
        if ($newHasWealth && $newWealth == $cost + 1)
        {
            return true;
        }
        return false;
    }

    private function recomputePaidTotals(Game $game): void
    {
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        foreach ($this->paidCardIds as $paidCardId)
        {
            $card = $game->theah->getCardById($paidCardId);
            if ($card === null)
            {
                continue;
            }
            $this->paidWealth += $card->hasTrait("Wealth") ? 2 : 1;
            if ($card->hasTrait("Wealth"))
            {
                $this->paidHasWealthCard = true;
            }
        }
    }
}
