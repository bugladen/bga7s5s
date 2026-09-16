<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
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
            $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
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
            $seen = [];
            foreach ($this->getArtifactsInCityDeck($game) as $card)
            {
                if (isset($seen[$card->Name]))
                {
                    continue;
                }
                if (count($this->getEligibleHomeHosts($theah, $owner, $card)) == 0)
                {
                    continue;
                }
                $seen[$card->Name] = true;
                $array[] = $this->createButtonProperty($game, $card->Name, 'search-' . $card->Id);
            }
            $array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');
        }

        if ($this->stage === 'pick')
        {
            $array[] = $this->createButtonProperty($game, $game->translate('< Back'), 'back');
            $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
            if ($attachment instanceof Attachment)
            {
                foreach ($this->getEligibleHomeHosts($theah, $owner, $attachment) as $character)
                {
                    $cost = $this->equipCost($theah, $character, $attachment);
                    $label = sprintf(
                        $game->translate('Equip to %s (cost %d)'),
                        $character->Name,
                        $cost
                    );
                    $array[] = $this->createButtonProperty($game, $label, 'equip_' . $character->Id);
                }
            }
            $array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');
        }

        if ($this->stage === 'pay')
        {
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

            if (! $this->hasAffordableArtifactSearch($event->theah, $owner))
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
            || count($this->getEligibleHomeHosts($game->theah, $owner, $attachment)) == 0)
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
        foreach ($this->getEligibleHomeHosts($game->theah, $owner, $attachment) as $character)
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

        [$discount, $explanations] = $game->theah->getEquipDiscount($match, $attachment);
        $cost = $attachment->WealthCost - $discount;
        if ($cost < 0)
        {
            $cost = 0;
        }

        $this->chosenCharacterId = $match->Id;
        $this->paidDiscount = $discount;
        $this->paidExplanations = is_string($explanations) ? $explanations : '';
        $this->paidCost = $cost;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $owner->IsUpdated = true;

        if ($cost <= 0)
        {
            $this->finalize($game, $owner);
            return;
        }

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

        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} searched the City Deck and equips ${attachment_inject_code} to ${character_inject_code}.'), [
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

        $this->shuffleCityDeck($game, $owner);
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

    private function hasAffordableArtifactSearch(Theah $theah, Character $owner): bool
    {
        foreach ($this->getArtifactsInCityDeck($theah->game) as $attachment)
        {
            if (count($this->getEligibleHomeHosts($theah, $owner, $attachment)) > 0)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * WHY inject codes: search buttons only show plain Name strings (no hover
     * tooltip). Monet Reaction_04023 uses the same implode(getInjectCode) pattern.
     * WHY cards[]: City Deck Artifacts are not in opponents' cardProperties;
     * format_string_recursive_with_injection seeds logCardCache from notify args
     * (id+type objects, including arrays) — same as gamble reveal / Risk play.
     */
    private function notifyArtifactSearch(Game $game, Character $owner): void
    {
        $artifacts = $this->getArtifactsInCityDeck($game);
        $names = [];
        $cards = [];
        foreach ($artifacts as $attachment)
        {
            $names[] = $attachment->getInjectCode();
            $cards[] = $attachment->getPropertyArray($game);
        }

        $game->notify->all(
            'message',
            clienttranslate('${reaction_inject_code}: ${player_name} searches the City Deck for Artifacts (${count}): ${names}'),
            [
                'reaction_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'count' => count($names),
                'names' => implode(', ', $names),
                'cards' => $cards,
            ]
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
     * @return Character[]
     */
    private function getEligibleHomeHosts(Theah $theah, Character $owner, Attachment $attachment): array
    {
        $out = [];
        $handWealth = $theah->game->handWealthCount($owner->ControllerId);
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
            $cost = $this->equipCost($theah, $character, $attachment);
            if ($handWealth < $cost)
            {
                continue;
            }
            $out[] = $character;
        }
        return $out;
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
