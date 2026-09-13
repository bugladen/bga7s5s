<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01130 extends RiskAction
{
    public bool $IsActive = false;
    public int $ControllingCharacterId = 0;
    public string $ControlledLocation = "";

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Claim Location";
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        foreach ($performers as $performer)
        {
            if ($this->isViablePerformer($playerId, $theah, $performer))
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $availablePerformers = [];
        foreach ($performers as $performer)
        {
            if ($this->isViablePerformer($playerId, $theah, $performer))
            {
                $availablePerformers[] = $performer;
            }
        }

        return $availablePerformers;
    }

    private function isViablePerformer(int $playerId, Theah $theah, $performer): bool
    {
        $characters = $theah->getCharactersAtLocation($performer->Location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId == $playerId);
        $location = $theah->getCityLocation($performer->Location);
        // WHY: Route claim gate through canLocationBeClaimedBy (central API; $playerId reserved
        // for future per-player rules). Leshiye/etc. set CanBeClaimed false — IW cannot start there.
        return count($characters) == 1
            && ! $location->isControlled()
            && $theah->canLocationBeClaimedBy($playerId, $performer->Location);
    }

    private function setLocationClaimFlags(Theah $theah, string $locationName, bool $canBeClaimed, bool $canBecomeUncontrolled): void
    {
        // WHY: IW toggles both flags together; Theah helpers own the dual-write (globals +
        // in-memory) so same-request claim/uncontrol guards never see a stale property.
        $theah->setLocationCanBeClaimed($locationName, $canBeClaimed);
        $theah->setLocationCanBecomeUncontrolled($locationName, $canBecomeUncontrolled);
    }

    /**
     * End Indomitable Will for a character. Idempotent — safe when Character and every
     * discarded Action_01130 copy all see the same leave/destroy event.
     *
     * WHY character-side call sites exist: lasting state used to live only on the played
     * Risk's Action. With two copies, a prior copy in discard (or a copy reshuffled into
     * the faction deck, which buildCity does not load) can leave IsActive out of sync so
     * destroy never clears control. The condition on the character is the correlator;
     * location + flags clear from here even when no Action_01130 is in the event loop.
     */
    public static function endEffect(Game $game, Character $character, string $location): void
    {
        $hadCondition = $character->hasCondition(Game::INDOMITABLE_WILL_CONDITION);
        $clearedAnyAction = self::clearActionsTrackingCharacter($game, $character->Id);

        if ( ! $hadCondition && ! $clearedAnyAction)
        {
            return;
        }

        if ($hadCondition)
        {
            $character->removeCondition(Game::INDOMITABLE_WILL_CONDITION);

            $game->notify->all("indomitableWillConditionEnded", '${character_inject_code} has lost Indomitable Will.', [
                "character_inject_code" => $character->getInjectCode(),
                "cardId" => $character->Id,
            ]);
        }

        if ( ! $game->theah->locationInCity($location))
        {
            return;
        }

        // WHY: Clear CanBecomeUncontrolled before queueing the uncontrolled event so the
        // emit-site guard below (and any future ones) lets THIS legitimate uncontrol pass.
        // No Leshiye overlap to consider — IW cannot be active at a Leshiye location.
        $game->theah->setLocationCanBeClaimed($location, true);
        $game->theah->setLocationCanBecomeUncontrolled($location, true);

        $cityLocation = $game->theah->getCityLocation($location);
        if ($cityLocation->Controller != 0)
        {
            $controllerId = $cityLocation->Controller;
            $locationUncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent($controllerId, $location);
            $game->theah->queueEvent($locationUncontrolledEvent);
        }
    }

    private static function clearActionsTrackingCharacter(Game $game, int $characterId): bool
    {
        $cleared = false;

        foreach ($game->theah->getWorldCards() as $card)
        {
            if (self::clearActionOnCard($card, $characterId))
            {
                $cleared = true;
            }
        }

        // WHY: Faction decks are not in buildCity(). A prior IW Risk can be reshuffled
        // there still carrying IsActive; clear it so a later dusk/draw cannot revive
        // stale ControlledLocation cleanup against the wrong board state.
        $playerIds = $game->loadPlayersBasicInfos();
        foreach (array_keys($playerIds) as $playerId)
        {
            $deckName = $game->getPlayerFactionDeckName((int)$playerId);
            $deckRows = $game->getGameDeckObject()->getCardsInLocation($deckName);
            foreach ($deckRows as $row)
            {
                $card = $game->getCardObjectFromDb((int)$row['id']);
                if ($card === null)
                {
                    continue;
                }
                if (self::clearActionOnCard($card, $characterId))
                {
                    $game->updateCardObjectInDb($card);
                    $cleared = true;
                }
            }
        }

        return $cleared;
    }

    private static function clearActionOnCard($card, int $characterId): bool
    {
        if ( ! $card instanceof IHasActions)
        {
            return false;
        }

        $cleared = false;
        foreach ($card->getActions() as $action)
        {
            if ( ! $action instanceof self)
            {
                continue;
            }

            if ($action->IsActive && $action->ControllingCharacterId == $characterId)
            {
                $action->IsActive = false;
                $action->ControllingCharacterId = 0;
                $action->ControlledLocation = "";
                $card->IsUpdated = true;
                $cleared = true;
            }
        }

        return $cleared;
    }

    private function setConditionEnded(Game $game): void
    {
        $character = $game->theah->getCharacterById($this->ControllingCharacterId);
        $location = $this->ControlledLocation;
        if ($character === null)
        {
            // WHY: Character may already be a locker recreate mid-pipeline; still clear
            // Action state and location flags using the stored ControlledLocation.
            self::clearActionsTrackingCharacter($game, $this->ControllingCharacterId);
            if ($game->theah->locationInCity($location))
            {
                $this->setLocationClaimFlags($game->theah, $location, true, true);
                $cityLocation = $game->theah->getCityLocation($location);
                if ($cityLocation->Controller != 0)
                {
                    $game->theah->queueEvent(
                        EventFactory::createLocationBecomesUncontrolledEvent($cityLocation->Controller, $location)
                    );
                }
            }
            return;
        }

        self::endEffect($game, $character, $location !== '' ? $location : $character->Location);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ( ! $event->theah->canLocationBeClaimedBy($performer->ControllerId, $performer->Location))
            {
                $game->notify->all("message", clienttranslate('${location} cannot be claimed.'), [
                    'i18n' => ['location'],
                    'location' => $performer->Location,
                ]);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
                $event->theah->queueEvent($actionResolvedEvent);
                return;
            }

            $this->IsActive = true;
            $this->ControllingCharacterId = $performer->Id;
            $this->ControlledLocation = $performer->Location;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;

            $performer->addCondition(Game::INDOMITABLE_WILL_CONDITION);

            // WHY: Queue Yevgeni's own claim BEFORE flipping CanBeClaimed off, otherwise the
            // claim emit-site guard added to all claim sources would skip Yevgeni's claim too.
            $claimEvent = EventFactory::createLocationClaimedEvent($performer->ControllerId, $performerId, $performer->Location);
            $event->theah->queueEvent($claimEvent);

            $this->setLocationClaimFlags($event->theah, $performer->Location, false, false);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);

            $game->notify->all("indomitableWillConditionStarted", '${character_inject_code} has gained Indomitable Will.', [
                "character_inject_code" => $performer->getInjectCode(),
                "cardId" => $performer->Id,
            ]);
       }

       if ($event instanceof EventCardMoved && $this->IsActive)
       {
            if ($event->cardId == $this->ControllingCharacterId && $event->toLocation != $this->ControlledLocation)
            {
                $this->setConditionEnded($event->theah->game);
            }
       }

       if ($event instanceof EventCardDiscardedFromPlay && $this->IsActive)
       {
            if ($event->cardId == $this->ControllingCharacterId)
            {
                $this->setConditionEnded($event->theah->game);
            }
       }

       if ($event instanceof EventCharacterDestroyed && $this->IsActive)
       {
            if ($event->characterId == $this->ControllingCharacterId)
            {
                $this->setConditionEnded($event->theah->game);
            }
       }

       if ($event instanceof EventDuskEndOfDay && $this->IsActive)
       {
            $this->setConditionEnded($event->theah->game);
       }
    }
}
