<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IPayTimeCostDiscount;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04042 extends CardReaction
{
    // '' idle, 'search', 'pick', 'pay'
    private string $stage = '';
    private int $pendingAttachmentId = 0;
    private int $chosenCharacterId = 0;
    private int $paidDiscount = 0;
    private string $paidExplanations = '';
    private int $paidCost = 0;

    /** @var array<int> */
    private array $paidCardIds = [];
    private int $paidWealth = 0;
    private bool $paidHasWealthCard = false;
    private bool $didSearch = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Search City Deck for an Artifact and equip it at Home");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->stage === 'pay')
        {
            $owner = $this->getOwningCharacter($theah);
            if ($owner !== null)
            {
                $this->refreshPaymentCost($theah, $owner);
            }
            $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
            if ($this->paidCost <= 0)
            {
                return $base . sprintf(
                    $theah->game->translate('${you} may equip %s (cost reduced to 0): '),
                    $attachment ? $attachment->Name : ''
                );
            }
            return $base . sprintf(
                $theah->game->translate('Pay %d Wealth for %s — click cards in your hand. Paid so far: %d.'),
                $this->paidCost,
                $attachment ? $attachment->Name : '',
                $this->paidWealth
            );
        }

        if ($this->stage === 'pick')
        {
            return $base . $theah->game->translate('${you} must choose a character at Home to equip the Artifact to:');
        }

        return $base . $theah->game->translate('${you} may search the City Deck for an Artifact to equip to your character at Home, paying all costs: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $game = $theah->game;
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return $array;
        }

        if ($this->stage === 'search')
        {
            // Dedupe by Name — identical City Deck copies are indistinguishable.
            // WHY no affordability filter: Leader Yevgeni (_01116) can discount
            // non-character cards during pay; Pass covers anything still unaffordable.
            $seen = [];
            foreach ($this->getArtifactsInCityDeck($game) as $card)
            {
                if (isset($seen[$card->Name]))
                {
                    continue;
                }
                if (count($this->getAttachableHomeHosts($theah, $owner, $card)) == 0)
                {
                    continue;
                }
                $seen[$card->Name] = true;
                $label = sprintf($game->translate('%s (Cost: %d)'), $card->Name, $card->WealthCost);
                $array[] = $this->createButtonProperty($game, $label, 'search-' . $card->Id);
            }
            $array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');
        }

        if ($this->stage === 'pick')
        {
            $array[] = $this->createButtonProperty($game, $game->translate('< Back'), 'back');
            $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
            if ($attachment instanceof Attachment)
            {
                foreach ($this->getAttachableHomeHosts($theah, $owner, $attachment) as $character)
                {
                    $label = sprintf(
                        $game->translate('Equip %s to %s'),
                        $attachment->Name,
                        $character->Name
                    );
                    $array[] = $this->createButtonProperty($game, $label, 'equip_' . $character->Id);
                }
            }
            $array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');
        }

        if ($this->stage === 'pay')
        {
            $this->refreshPaymentCost($theah, $owner);

            // After Yevgeni (or other pay-time discounts) the cost may drop to 0.
            if ($this->paidCost <= 0)
            {
                $array[] = $this->createButtonProperty($game, $game->translate('Equip'), 'confirmEquip');
                $array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');
                return $array;
            }

            $array[] = $this->createButtonProperty($game, $game->translate('< Back'), 'back');
            $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            foreach ($hand as $card)
            {
                if (in_array($card->Id, $this->paidCardIds, true))
                {
                    continue;
                }
                if ($card->Id == $this->pendingAttachmentId)
                {
                    continue;
                }
                if (! $this->wouldClickProduceValidPayment($card, $this->paidCost))
                {
                    continue;
                }

                $wealth = $card->hasTrait("Wealth") ? 2 : 1;
                $label = sprintf($game->translate('Pay with %s (+%d Wealth)'), $card->Name, $wealth);
                $array[] = $this->createButtonProperty($game, $label, 'pay-' . $card->Id);
            }
            $array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Approach muster emits EventApproachCharacterPlayed, not EventCharacterMustered.
        if (($event instanceof EventCharacterMustered || $event instanceof EventApproachCharacterPlayed)
            && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner === null || $event->characterId != $owner->Id)
            {
                return;
            }

            // WHY no wealth/affordability gate: Approach before Planning Draw often
            // means empty hand, but 0-cost Artifacts exist and Leader Yevgeni can
            // discount during pay. Fire when ≥1 Artifact has a legal Home host;
            // Pass exits (and shuffles) when nothing can be paid for.
            if (! $this->hasSearchableArtifact($event->theah, $owner))
            {
                return;
            }

            $this->resetState($owner);
            $this->stage = 'search';
            $this->didSearch = true;
            $owner->IsUpdated = true;

            $this->notifyArtifactSearch($event->theah->game, $owner);

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
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
            $this->finishWithoutEquip($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'confirmEquip')
        {
            $this->refreshPaymentCost($game->theah, $owner);
            if ($this->paidCost <= 0)
            {
                $this->finalize($game, $owner);
                return;
            }
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'back')
        {
            $this->handleBack($game, $owner);
            return;
        }

        if (str_starts_with($reactionId, 'search-'))
        {
            $this->handleSearchPick($game, $owner, $reactionId);
            return;
        }

        if (str_starts_with($reactionId, 'equip_'))
        {
            $this->handleEquipPick($game, $owner, $reactionId);
            return;
        }

        if (str_starts_with($reactionId, 'pay-'))
        {
            $this->handlePay($game, $owner, $reactionId);
            return;
        }

        $game->gamestate->nextState("done");
    }

    private function handleSearchPick(Game $game, Character $owner, string $reactionId): void
    {
        $cardId = (int) substr($reactionId, strlen('search-'));
        $attachment = $game->theah->getAttachmentById($cardId);

        if (! ($attachment instanceof Attachment)
            || $attachment->Location != Game::LOCATION_CITY_DECK
            || ! $attachment->hasTrait("Artifact")
            || count($this->getAttachableHomeHosts($game->theah, $owner, $attachment)) == 0)
        {
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $this->pendingAttachmentId = $attachment->Id;
        $this->stage = 'pick';
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $owner->IsUpdated = true;
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handleEquipPick(Game $game, Character $owner, string $reactionId): void
    {
        $characterId = (int) substr($reactionId, strlen('equip_'));
        $attachment = $game->theah->getAttachmentById($this->pendingAttachmentId);
        if (! ($attachment instanceof Attachment))
        {
            $this->finishWithoutEquip($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $match = null;
        foreach ($this->getAttachableHomeHosts($game->theah, $owner, $attachment) as $character)
        {
            if ($character->Id == $characterId)
            {
                $match = $character;
                break;
            }
        }
        if ($match === null)
        {
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $this->chosenCharacterId = $match->Id;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $owner->IsUpdated = true;

        // WHY: If a pay-time discount (Yevgeni / Daniella / …) already Activated then the
        // player Back'd to another Artifact, DiscountedCardId still points at the old
        // card — retarget before cost calc.
        $this->syncPayTimeDiscountTargets($game, $owner, $attachment->Id);

        [$discount, $explanations] = $game->theah->getEquipDiscount($match, $attachment);
        $cost = $attachment->WealthCost - $discount;
        if ($cost < 0)
        {
            $cost = 0;
        }

        $this->paidDiscount = $discount;
        $this->paidExplanations = is_string($explanations) ? $explanations : '';
        $this->paidCost = $cost;

        if ($cost <= 0)
        {
            $this->finalize($game, $owner);
            return;
        }

        // WHY: Leader Yevgeni (_01116) listens on EventEnteringPayState for -1 cost on
        // non-character payments. Click-to-pay must emit it before the pay UI, with
        // CHOSEN_PERFORMER set so calculateInHandPayDiscount can re-run getEquipDiscount
        // after Activate. Cost is refreshed when the pay stage UI loads.
        $game->globals->set(Game::CHOSEN_PERFORMER, $match->Id);
        $payEvent = EventFactory::createEnteringPayStateEvent(
            $owner->ControllerId,
            $attachment->Id,
            Game::PAY_STATE_EQUIP_ATTACHMENT
        );
        $game->theah->queueEvent($payEvent);

        $this->stage = 'pay';
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

        $this->refreshPaymentCost($game->theah, $owner);
        if ($this->paidCost <= 0)
        {
            $this->finalize($game, $owner);
            return;
        }

        $cardId = (int) substr($reactionId, strlen('pay-'));
        $card = $game->theah->getCardById($cardId);

        if ($card === null
            || $card->Location !== Game::LOCATION_HAND
            || $card->OwnerId !== $owner->ControllerId
            || in_array($card->Id, $this->paidCardIds, true)
            || $card->Id == $this->pendingAttachmentId)
        {
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (! $this->wouldClickProduceValidPayment($card, $this->paidCost))
        {
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
            $this->finalize($game, $owner);
            return;
        }

        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handleBack(Game $game, Character $owner): void
    {
        if ($this->stage === 'pay' && count($this->paidCardIds) > 0)
        {
            array_pop($this->paidCardIds);
            $this->recomputePaidTotals($game);
            $owner->IsUpdated = true;
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if ($this->stage === 'pay')
        {
            $this->stage = 'pick';
            $this->chosenCharacterId = 0;
            $this->paidCost = 0;
            $owner->IsUpdated = true;
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if ($this->stage === 'pick')
        {
            $this->stage = 'search';
            $this->pendingAttachmentId = 0;
            $this->chosenCharacterId = 0;
            $owner->IsUpdated = true;
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $game->gamestate->nextState("done");
    }

    private function finalize(Game $game, Character $owner): void
    {
        $attachment = $game->theah->getAttachmentById($this->pendingAttachmentId);
        $character = $game->theah->getCharacterById($this->chosenCharacterId);
        if (! ($attachment instanceof Attachment)
            || ! ($character instanceof Character)
            || $attachment->Location != Game::LOCATION_CITY_DECK
            || $character->Location != Game::LOCATION_PLAYER_HOME
            || $character->ControllerId != $owner->ControllerId)
        {
            $this->finishWithoutEquip($game, $owner);
            $game->gamestate->nextState("done");
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

        // WHY top-level card property arrays: attachment was in the City Deck —
        // opponents lack it in cardProperties. Seed logCardCache via id+type objects
        // (combat-card announce pattern), not only a nested cards[] array.
        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} searched the City Deck and equips ${attachment_inject_code} to ${character_inject_code}.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
            "attachment_inject_code" => $attachment->getInjectCode(),
            "character_inject_code" => $character->getInjectCode(),
            "card" => $owner->getPropertyArray($game),
            "card_" . $attachment->Id => $attachment->getPropertyArray($game),
            "card_" . $character->Id => $character->getPropertyArray($game),
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

        $this->shuffleCityDeck($game, $owner);
        $this->clearPayTimeDiscounts($game, $owner);
        $this->resetState($owner);
        $this->setUsed($game->theah, true);

        $game->gamestate->nextState("done");
    }

    private function finishWithoutEquip(Game $game, Character $owner): void
    {
        // WHY: parenthetical Shuffle applies once the deck was searched (button list shown).
        if ($this->didSearch)
        {
            $this->shuffleCityDeck($game, $owner);
        }
        // WHY: pay-time IsActive is not cleared by ActionResolved here — clear so Back/Pass
        // mid-flow cannot leak the -1 onto a later unrelated equip this turn.
        $this->clearPayTimeDiscounts($game, $owner);
        $this->resetState($owner);
        // Pass / abort does not burn the Reaction — muster only fires once anyway.
    }

    private function shuffleCityDeck(Game $game, Character $owner): void
    {
        $game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK);
        $game->notify->all("message", clienttranslate('${player_name} shuffles the City Deck.'), [
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
        ]);
    }

    private function requeue(Game $game, Character $owner): void
    {
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($transition);
    }

    /**
     * WHY: After Activate, Back + a different Artifact would leave DiscountedCardId on the
     * old card (pay-time discounts scope -1 to that id). Retarget so the discount follows
     * the new pick — any IPayTimeCostDiscount (Yevgeni, Daniella Faith/Sorcery, …).
     */
    private function syncPayTimeDiscountTargets(Game $game, Character $owner, int $attachmentId): void
    {
        foreach ($this->getActivePayTimeDiscounts($game, $owner) as [$reaction, $card])
        {
            $reaction->retargetDiscountedCard($attachmentId);
            $card->IsUpdated = true;
        }
    }

    private function clearPayTimeDiscounts(Game $game, Character $owner): void
    {
        foreach ($this->getActivePayTimeDiscounts($game, $owner) as [$reaction, $card])
        {
            $reaction->clearActiveDiscount();
            $card->IsUpdated = true;
        }
    }

    /**
     * @return array<int, array{0: IPayTimeCostDiscount, 1: Card}>
     */
    private function getActivePayTimeDiscounts(Game $game, Character $owner): array
    {
        $out = [];
        foreach ($game->theah->getAllCards() as $card)
        {
            if (! ($card instanceof IHasReactions)
                || $card->ControllerId != $owner->ControllerId)
            {
                continue;
            }

            foreach ($card->getReactions() as $reaction)
            {
                if ($reaction instanceof IPayTimeCostDiscount && $reaction->isDiscountActive())
                {
                    $out[] = [$reaction, $card];
                }
            }
        }
        return $out;
    }

    private function resetState(?Character $owner): void
    {
        $this->stage = '';
        $this->pendingAttachmentId = 0;
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $this->paidDiscount = 0;
        $this->paidExplanations = '';
        $this->paidCost = 0;
        $this->didSearch = false;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function hasSearchableArtifact(Theah $theah, Character $owner): bool
    {
        foreach ($this->getArtifactsInCityDeck($theah->game) as $attachment)
        {
            // Ignore wealth — 0-cost Artifacts and pay-time discounts (Yevgeni).
            if (count($this->getAttachableHomeHosts($theah, $owner, $attachment)) > 0)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * WHY inject codes: search buttons only show plain Name strings (no hover
     * tooltip). Monet Reaction_04023 uses the same implode(getInjectCode) pattern.
     * WHY top-level card_* property arrays (not only cards[]): format_string_recursive
     * seeds logCardCache from notify args with id+type. Nested cards[] only hydrates
     * if the Array.isArray scan is present; singular top-level objects match the
     * combat-card / Risk-play pattern and work with the older scanner too.
     */
    private function notifyArtifactSearch(Game $game, Character $owner): void
    {
        $artifacts = $this->getArtifactsInCityDeck($game);
        $names = [];
        $args = [
            'reaction_inject_code' => $owner->getInjectCode(),
            'player_name' => $game->getPlayerNameById($owner->ControllerId),
            'card' => $owner->getPropertyArray($game),
        ];
        foreach ($artifacts as $attachment)
        {
            $names[] = $attachment->getInjectCode();
            $args['card_' . $attachment->Id] = $attachment->getPropertyArray($game);
        }
        $args['count'] = count($names);
        $args['names'] = implode(', ', $names);

        $game->notify->all(
            'message',
            clienttranslate('${reaction_inject_code}: ${player_name} searches the City Deck for Artifacts (${count}): ${names}'),
            $args
        );
    }

    /**
     * @return Attachment[]
     */
    private function getArtifactsInCityDeck(Game $game): array
    {
        $out = [];
        foreach ($game->theah->getCardObjectsAtLocation(Game::LOCATION_CITY_DECK) as $card)
        {
            if ($card instanceof Attachment && $card->hasTrait("Artifact") && ! $card->FakeAttachment)
            {
                $out[] = $card;
            }
        }
        return $out;
    }

    /**
     * Legal Home hosts (attach + restrictions only — no wealth gate).
     * WHY: Leader Yevgeni can discount during pay; Pass covers unaffordable cases.
     *
     * @return Character[]
     */
    private function getAttachableHomeHosts(Theah $theah, Character $owner, Attachment $attachment): array
    {
        $out = [];
        // WHY: Home is a shared location string — never getCharactersAtLocation(HOME).
        foreach ($theah->getCharactersAtHomeByPlayerId($owner->ControllerId) as $character)
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
            $out[] = $character;
        }
        return $out;
    }

    private function refreshPaymentCost(Theah $theah, Character $owner): void
    {
        $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
        $character = $theah->getCharacterById($this->chosenCharacterId);
        if (! ($attachment instanceof Attachment) || ! ($character instanceof Character))
        {
            return;
        }

        [$discount, $explanations] = $theah->getEquipDiscount($character, $attachment);
        $cost = $attachment->WealthCost - $discount;
        if ($cost < 0)
        {
            $cost = 0;
        }

        $this->paidDiscount = $discount;
        $this->paidExplanations = is_string($explanations) ? $explanations : '';
        $this->paidCost = $cost;
        $owner->IsUpdated = true;
    }

    private function equipCost(Theah $theah, Character $performer, Attachment $attachment): int
    {
        [$discount] = $theah->getEquipDiscount($performer, $attachment);
        $cost = $attachment->WealthCost - $discount;
        return $cost < 0 ? 0 : $cost;
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
