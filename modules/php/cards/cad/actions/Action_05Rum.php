<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Rum;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Pirate City Action: If your performer is at the chosen location • Pressure it with
// [Finesse]. If successful, search your deck for a Unique Attachment and equip it to
// your performer paying all costs.
class Action_05Rum extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Finesse Pressure; search Unique Attachment";
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        if (! ($owner instanceof _05Rum) || $owner->ChosenLocation === '')
        {
            return [];
        }

        $characters = $theah->getCharactersAtLocationByPlayerId($owner->ChosenLocation, $playerId);
        $pirates = array_filter(
            $characters,
            fn($c) => $c instanceof Character
                && $c->hasTrait("Pirate")
                && $c->canPressure(Game::STAT_FINESSE)
        );

        return array_values($pirates);
    }

    /**
     * Unique Attachments in the faction deck the performer can legally equip and afford.
     *
     * @return list<Attachment>
     */
    private function getEquippableUniqueAttachments(Game $game, Character $performer): array
    {
        $deckName = $game->getPlayerFactionDeckName($performer->ControllerId);
        $deckObject = $game->getGameDeckObject();
        $deck = $deckObject->getCardsInLocation($deckName);
        $handWealth = $game->handWealthCount($performer->ControllerId);
        $matches = [];

        foreach ($deck as $deckCard)
        {
            $card = $game->getCardObjectFromDb((int)$deckCard['id']);
            if (! ($card instanceof Attachment) || $card->FakeAttachment || ! $card->hasTrait("Unique"))
            {
                continue;
            }

            if (! $card->canAttachTo($performer))
            {
                continue;
            }

            [$hasRestrictions] = $game->hasEquipRestrictions($performer, $card);
            if ($hasRestrictions)
            {
                continue;
            }

            [$discount] = $game->theah->getEquipDiscount($performer, $card);
            $cost = $card->WealthCost - $discount;
            if ($cost < 0)
            {
                $cost = 0;
            }

            // WHY: cost 0 is always affordable (empty hand still legal).
            if ($cost > 0 && $handWealth < $cost)
            {
                continue;
            }

            $matches[] = $card;
        }

        return $matches;
    }

    // WHY: Full override — SchemeAction requires Location == PLAYER_HOME,
    // but this scheme lives in the city after placement. Replicate the
    // CardAction checks (owner controlled + not used) manually. Mirror Action_02045.
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        $owner = $this->getOwningCard($theah);

        if ($owner->isControlled() && $owner->ControllerId != $playerId && ! $overrideInHandCheck)
        {
            return false;
        }

        if ($this->Used)
        {
            return false;
        }

        if (! ($owner instanceof _05Rum) || $owner->ChosenLocation === '')
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
            $game = $event->theah->game;

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if (! ($owner instanceof _05Rum) || $owner->ChosenLocation === '')
            {
                throw new UserException("No location has been chosen for this scheme.");
            }

            if ($performer === null || $performer->Location !== $owner->ChosenLocation)
            {
                throw new UserException("Performer must be at the chosen location.");
            }

            if (! $performer->hasTrait("Pirate"))
            {
                throw new UserException("Performer must be a Pirate.");
            }

            $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);

            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_FINESSE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent(
                $owner->ControllerId,
                $performer->Id,
                $performer->Location,
                $pressureStats
            );
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);

            if ($event->success)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Rum", $this->Id);
                $event->theah->queueEvent($transition);
            }
            else
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
                $event->theah->queueEvent($actionResolvedEvent);
            }
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05RUM)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $matches = $performer instanceof Character
                ? $this->getEquippableUniqueAttachments($game, $performer)
                : [];
            // WHY array_values: non-sequential keys JSON-encode as objects; chooseList.forEach breaks.
            $args["cards"] = array_values(array_map(
                fn(Attachment $card) => $card->getPropertyArray($game),
                $matches
            ));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05RUM_2)
        {
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            $characterId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $args['cardId'] = $attachmentId;
            $args['characterId'] = $characterId;
            $args['cost'] = (int)$game->globals->get(Game::CHOSEN_CARD_COST);
            $args['discount'] = (int)$game->globals->get(Game::DISCOUNT, 0);
            if ($attachment !== null)
            {
                $args['card'] = $attachment->getPropertyArray($game);
            }
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05RUM)
        {
            $owner = $this->getOwningCard($game->theah);
            $playerId = $game->getActivePlayerId();
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if (! ($performer instanceof Character))
            {
                throw new UserException("Performer not found.");
            }

            $card = $game->theah->getCardById($id);
            if ($card === null)
            {
                throw new UserException("Invalid card ID.");
            }

            $deckName = $game->getPlayerFactionDeckName($playerId);
            if ($card->Location != $deckName)
            {
                throw new UserException("Card is not in your Faction Deck.");
            }

            if (! ($card instanceof Attachment) || $card->FakeAttachment || ! $card->hasTrait("Unique"))
            {
                throw new UserException("Card must be a Unique Attachment.");
            }

            $eligibleIds = array_map(
                fn(Attachment $attachment) => $attachment->Id,
                $this->getEquippableUniqueAttachments($game, $performer)
            );
            if (! in_array($card->Id, $eligibleIds, true))
            {
                throw new UserException("That Unique Attachment cannot be equipped to your performer.");
            }

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $card->Id);
            $game->globals->set(Game::CHOSEN_CARD_COST, $card->WealthCost);

            $card->ControllerId = $performer->ControllerId;
            $game->updateCardObjectInDb($card);
            $game->theah->addCardToWorld($card);

            $game->notify->all("message", '${scheme_inject_code}: ${player_name} found ${card_inject_code} in their deck.', [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($playerId, $card->Id);
            $game->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
            $game->theah->queueEvent($addEvent);

            // WHY: Deck was searched — shuffle even though Text omits "(Shuffle your deck.)".
            $game->getGameDeckObject()->shuffle($deckName);
            $game->notify->all("message", '${player_name} shuffles their deck.', [
                "player_name" => $game->getPlayerNameById($playerId),
            ]);

            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $card->Id, Game::PAY_STATE_EQUIP_ATTACHMENT);
            $game->theah->queueEvent($payEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Rum_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("attachmentChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05RUM_2)
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

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05RUM)
        {
            $playerId = $game->getActivePlayerId();
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $matches = $performer instanceof Character
                ? $this->getEquippableUniqueAttachments($game, $performer)
                : [];

            if (count($matches) > 0)
            {
                throw new UserException("There are Unique Attachments in your Faction Deck you can equip.");
            }

            $deckName = $game->getPlayerFactionDeckName($playerId);
            $game->getGameDeckObject()->shuffle($deckName);
            $game->notify->all("message", '${player_name} shuffles their deck.', [
                "player_name" => $game->getPlayerNameById($playerId),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
        }
    }
}
