<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Covert Action: Send a Bureaucracy or Discovery in your discard pile to The Locker •
// En garde target character.
class Action_05Serenissima extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Covert: Send Bureaucracy/Discovery to Locker; En Garde Target";
    }

    /**
     * @return list<Card>
     */
    private function getEligibleDiscardCards(Theah $theah, Character $owner): array
    {
        $discardName = $theah->game->getPlayerDiscardDeckName($owner->ControllerId);
        $eligible = [];
        foreach ($theah->getCardObjectsAtLocation($discardName) as $card)
        {
            if ($card->hasTrait("Bureaucracy") || $card->hasTrait("Discovery"))
            {
                $eligible[] = $card;
            }
        }

        return $eligible;
    }

    /**
     * Printed "target character" with no opposing/your — Engaged characters at Patricia's
     * location (Covert site). WHY Engaged for the picker/effect only: En Garde is right of
     * • (effect), not a left-of-• cost — but Engarde only changes Engaged characters
     * (01152a / 02051). Ready characters must not highlight.
     *
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $owner): array
    {
        $atLocation = $theah->getCharactersAtLocation($owner->Location);
        return array_values(array_filter(
            $atLocation,
            fn(Character $character) => $character->Engaged
        ));
    }

    private function isCovertSite(Theah $theah, Character $owner): bool
    {
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $cityLocation = $theah->getCityLocation($owner->Location);
        return $cityLocation !== null && ! $cityLocation->isControlled();
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $this->isCovertSite($theah, $owner))
        {
            return false;
        }

        return count($this->getEligibleDiscardCards($theah, $owner)) > 0
            && count($this->getValidTargets($theah, $owner)) > 0;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);

        if ($character->Location != $owner->Location)
        {
            return [false, "Target must be at Patricia's location."];
        }

        if (! $character->Engaged)
        {
            return [false, "Character is not engaged."];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Serenissima", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $this->resolveTargeted($event);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);
        $owner = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA)
        {
            $args['performerId'] = $owner->Id;
            // WHY array_values: non-sequential keys JSON-encode as objects; chooseList.forEach breaks.
            $args['cards'] = array_values(array_map(
                fn(Card $card) => $card->getPropertyArray($game),
                $this->getEligibleDiscardCards($game->theah, $owner)
            ));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA_2)
        {
            $args['performerId'] = $owner->Id;
            $args['ids'] = array_values(array_map(
                fn(Character $character) => $character->Id,
                $this->getValidTargets($game->theah, $owner)
            ));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA)
        {
            $owner = $this->getOwningCharacter($game->theah);

            // WHY: Keep Covert honest if control flipped between announce and resolve.
            if (! $this->isCovertSite($game->theah, $owner))
            {
                throw new UserException("Covert Actions can only be used at uncontrolled City locations.");
            }

            $card = $game->theah->getCardById($id);
            if ($card === null)
            {
                $card = $game->getCardObjectFromDb($id);
            }
            if ($card === null)
            {
                throw new UserException("Card not found.");
            }

            $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);
            if ($card->Location != $discardName)
            {
                throw new UserException("Card is not in your discard pile.");
            }

            if (! $card->hasTrait("Bureaucracy") && ! $card->hasTrait("Discovery"))
            {
                throw new UserException("Card must be a Bureaucracy or Discovery.");
            }

            if (count($this->getValidTargets($game->theah, $owner)) == 0)
            {
                throw new UserException("No engaged character at this location to En Garde.");
            }

            // WHY: Stash and defer locker until target pick — Back stays legal on the
            // discard step (Corncob Weapon stash shape). Locker then Engarde at resolve.
            $game->globals->set(Game::CHOSEN_CARD, $card->Id);
            $game->gamestate->nextState("cardChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA_2)
        {
            $owner = $this->getOwningCharacter($game->theah);

            if (! $this->isCovertSite($game->theah, $owner))
            {
                throw new UserException("Covert Actions can only be used at uncontrolled City locations.");
            }

            $discardCardId = (int)$game->globals->get(Game::CHOSEN_CARD);
            $discardCard = $game->theah->getCardById($discardCardId);
            if ($discardCard === null)
            {
                $discardCard = $game->getCardObjectFromDb($discardCardId);
            }
            if ($discardCard === null)
            {
                throw new UserException("Card not found.");
            }

            $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);
            if ($discardCard->Location != $discardName)
            {
                throw new UserException("Card is no longer in your discard pile.");
            }

            if (! $discardCard->hasTrait("Bureaucracy") && ! $discardCard->hasTrait("Discovery"))
            {
                throw new UserException("Card must be a Bureaucracy or Discovery.");
            }

            $target = $game->theah->getCharacterById($id);
            if ($target === null)
            {
                throw new UserException("Character not found.");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            // WHY: Permission pre-check stays at the pick so a veto still surfaces to the player
            // here (a throw inside the survive hook would crash event dispatch). The event
            // actually queued is rebuilt in handleEvent.
            $game->theah->eventCheck(EventFactory::createCardEngardedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            ));

            // WHY: Fire targeting alone here. Locker (the • cost), remove-from-discard,
            // En Garde and ActionResolved emit from handleEvent only when EventCharacterTargeted
            // survives (Cloud Bottle / UL cancel it) — gated with the effect so a canceled
            // hook does not permanently lose a discard card for nothing (01015 seat).
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }

    private function resolveTargeted(EventCharacterTargeted $event): void
    {
        $theah = $event->theah;
        $game = $theah->game;
        $owner = $this->getOwningCharacter($theah);
        $target = $theah->getCharacterById($event->targetId);
        if ($owner === null || $target === null)
        {
            return;
        }

        $batchId = $event->batchId ?? $game->getNextEventBatchId();

        $discardCardId = (int)$game->globals->get(Game::CHOSEN_CARD);
        $discardCard = $theah->getCardById($discardCardId) ?? $game->getCardObjectFromDb($discardCardId);
        $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);

        // WHY: If the cost card left the discard pile while targeting resolved, the cost cannot
        // be paid — skip the effect but still resolve the action so the turn does not stall.
        if ($discardCard !== null && $discardCard->Location == $discardName)
        {
            $game->notify->all("message", '${owner_inject_code}: ${card_inject_code} is sent to The Locker from the discard pile.', [
                "owner_inject_code" => $owner->getInjectCode(),
                "card_inject_code" => $discardCard->getInjectCode(),
            ]);

            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($discardCard->OwnerId, $discardCard->Id);
            $removeEvent->batchId = $batchId;
            $theah->queueEvent($removeEvent);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($discardCard->OwnerId, $discardCard->Id);
            $lockerEvent->batchId = $batchId;
            $theah->queueEvent($lockerEvent);

            // WHY createCardEngardedEvent (not Engaged): Engarde sets Engaged=false (02051).
            // Action_01152a incorrectly uses Engaged for an En Garde effect — do not copy it.
            $engardeEvent = EventFactory::createCardEngardedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $engardeEvent->batchId = $batchId;
            $theah->queueEvent($engardeEvent);
        }

        // ActionResolved stays unbatched (01160 convention).
        $theah->queueEvent(EventFactory::createActionResolvedEvent($owner->ControllerId));
    }
}
