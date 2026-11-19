<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

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

// Covert Action: Take control of a Weapon in any discard pile. Equip it to your character
// at this location, paying all costs.
class Action_05Corncob extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Covert: Take Control of Weapon from Discard and Equip";
    }

    /**
     * Where a discard-pile attachment sits. null = not in a pile we can take from.
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

        // Controllers may differ from OwnerId after take-control cycles — also accept current location name.
        $players = $game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player)
        {
            if ($attachment->Location == $game->getPlayerDiscardDeckName((int)$playerId))
            {
                return ["city" => false, "playerId" => (int)$playerId];
            }
        }

        return null;
    }

    private function canCharacterEquipWeapon(Theah $theah, Character $character, Attachment $weapon, int $handWealth): bool
    {
        if (! $weapon->canAttachTo($character))
        {
            return false;
        }

        [$hasRestrictions] = $theah->game->hasEquipRestrictions($character, $weapon);
        if ($hasRestrictions)
        {
            return false;
        }

        [$discount] = $theah->getEquipDiscount($character, $weapon);
        $cost = $weapon->WealthCost - $discount;
        if ($cost < 0)
        {
            $cost = 0;
        }

        // WHY: 0-cost equips stay legal with empty hand (Langschwert / Teens / 04029).
        return $handWealth >= $cost;
    }

    /**
     * Printed "equip it to your character at this location" — any controlled character at Owner's location.
     *
     * @return list<Character>
     */
    private function getCharactersAtOwnerLocation(Theah $theah, Character $owner): array
    {
        return $theah->getCharactersAtLocationByPlayerId($owner->Location, $owner->ControllerId);
    }

    /**
     * @return list<Character>
     */
    private function getEquipCandidates(Theah $theah, Character $owner, Attachment $weapon): array
    {
        if ($this->getDiscardPileOf($theah->game, $weapon) === null)
        {
            return [];
        }

        $handWealth = $theah->game->handWealthCount($owner->ControllerId);
        $candidates = [];
        foreach ($this->getCharactersAtOwnerLocation($theah, $owner) as $character)
        {
            if ($this->canCharacterEquipWeapon($theah, $character, $weapon, $handWealth))
            {
                $candidates[] = $character;
            }
        }

        return $candidates;
    }

    /**
     * Weapons in any discard pile that at least one character at Owner's location can equip/afford.
     *
     * @return list<Attachment>
     */
    private function getEligibleWeapons(Theah $theah, Character $owner): array
    {
        if (! $theah->cardInCity($owner))
        {
            return [];
        }

        $cityLocation = $theah->getCityLocation($owner->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            return [];
        }

        $piles = [Game::LOCATION_CITY_DISCARD];
        $players = $theah->game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player)
        {
            $piles[] = $theah->game->getPlayerDiscardDeckName((int)$playerId);
        }

        $weapons = [];
        foreach ($piles as $pile)
        {
            foreach ($theah->getCardObjectsAtLocation($pile) as $card)
            {
                if (! ($card instanceof Attachment) || $card->FakeAttachment || ! $card->hasTrait("Weapon"))
                {
                    continue;
                }

                if (count($this->getEquipCandidates($theah, $owner, $card)) == 0)
                {
                    continue;
                }

                $weapons[$card->Id] = $card;
            }
        }

        return array_values($weapons);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        return count($this->getEligibleWeapons($theah, $owner)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Corncob", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);
        $owner = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB)
        {
            $args['performerId'] = $owner->Id;
            $weapons = $this->getEligibleWeapons($game->theah, $owner);
            // WHY array_values: non-sequential keys JSON-encode as objects; chooseList.forEach breaks.
            $args['cards'] = array_values(array_map(
                fn(Attachment $weapon) => $weapon->getPropertyArray($game),
                $weapons
            ));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_2)
        {
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            $args['performerId'] = $owner->Id;
            $args['attachmentId'] = $attachmentId;
            if ($attachment instanceof Attachment)
            {
                $args['ids'] = array_values(array_map(
                    fn(Character $character) => $character->Id,
                    $this->getEquipCandidates($game->theah, $owner, $attachment)
                ));
            }
            else
            {
                $args['ids'] = [];
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_3)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB)
        {
            $owner = $this->getOwningCharacter($game->theah);

            // WHY: Keep Covert honest if control flipped between announce and resolve.
            $cityLocation = $game->theah->getCityLocation($owner->Location);
            if ($cityLocation === null || $cityLocation->isControlled())
            {
                throw new UserException("Covert Actions can only be used at uncontrolled City locations.");
            }

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null || $attachment->FakeAttachment || ! $attachment->hasTrait("Weapon"))
            {
                throw new UserException("That card is not a Weapon in a discard pile.");
            }

            if ($this->getDiscardPileOf($game, $attachment) === null)
            {
                throw new UserException("That Weapon is not in a discard pile.");
            }

            if (count($this->getEquipCandidates($game->theah, $owner, $attachment)) == 0)
            {
                throw new UserException("No character at this location can equip that Weapon.");
            }

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);
            $game->gamestate->nextState("weaponChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_2)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $cityLocation = $game->theah->getCityLocation($owner->Location);
            if ($cityLocation === null || $cityLocation->isControlled())
            {
                throw new UserException("Covert Actions can only be used at uncontrolled City locations.");
            }

            $attachmentId = (int)$game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            if (! ($attachment instanceof Attachment))
            {
                throw new UserException("Weapon not found.");
            }

            $pile = $this->getDiscardPileOf($game, $attachment);
            if ($pile === null)
            {
                throw new UserException("That Weapon is no longer in a discard pile.");
            }

            $candidateIds = array_map(
                fn(Character $character) => $character->Id,
                $this->getEquipCandidates($game->theah, $owner, $attachment)
            );
            if (! in_array($id, $candidateIds, true))
            {
                throw new UserException("That character cannot equip this Weapon (restriction or not enough Wealth).");
            }

            $character = $game->theah->getCharacterById($id);

            // WHY: PAY_STATE_EQUIP_ATTACHMENT discount + equip target both read CHOSEN_PERFORMER (Teens).
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

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Corncob_3", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_3)
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
