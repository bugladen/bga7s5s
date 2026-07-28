<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04003a extends CardReaction
{
    // WHY: EventCharacterDestroyed has no sourceId. Mark opponent-sourced lethal
    // wounds here so Destroyed can OR with IN_DUEL for the printed cause gate.
    private int $opponentLethalThugId = 0;

    private int $destroyedThugId = 0;

    // WHY: Per-player global deferral (not reaction-instance WaitAfterDuel).
    // stDuelNextPlayer treats Location=Hand as "alive elsewhere" and nullifies
    // remaining threat — aborting Round 2 while adversary still had threat.
    // Keep Thug in Locker/Discard until stDuelEnd → flushPendingRecovers.
    // Globals (not Desideria reaction fields): locker cards are absent from
    // buildCity, and Desideria's wound cost can reinject her and wipe fields.
    private static function pendingThugGlobalKey(int $playerId): string
    {
        return "desideria_04003_pending_thug_{$playerId}";
    }

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Desideria; put destroyed Thug into hand");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may wound Desideria to put the destroyed Thug into your hand: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound and Recover Thug'), 'recover');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterWounded && $this->isAvailable())
        {
            $this->maybeMarkOpponentLethalThug($event);
        }

        if ($event instanceof EventCharacterDestroyed && $this->isAvailable())
        {
            $this->maybeOfferRecover($event);
        }
    }

    private function maybeMarkOpponentLethalThug(EventCharacterWounded $event): void
    {
        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null)
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        if (! $event->theah->cardInCity($owner) || $owner->Engaged)
        {
            return;
        }

        $character = $event->theah->getCharacterById($event->characterId);
        if ($character === null)
        {
            return;
        }

        if (! $this->isYourThugAtLocation($character, $owner))
        {
            return;
        }

        // WHY: Wounds not yet applied — Character::handleEvent updates after parent
        // (and reactions) see EventCharacterWounded. Same threshold as Character destroy.
        $wouldDie = ($character->Wounds + $event->wounds) >= ($character->ModifiedResolve + $character->WoundsHealedIncoming);
        if (! $wouldDie)
        {
            return;
        }

        if (! $this->isOpponentSource($event->theah, $event->sourceId, $owner->ControllerId))
        {
            return;
        }

        $this->opponentLethalThugId = $character->Id;
        $owner->IsUpdated = true;
    }

    private function maybeOfferRecover(EventCharacterDestroyed $event): void
    {
        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null)
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        // City Reaction + En Garde precondition (not an Engage cost).
        if (! $event->theah->cardInCity($owner) || $owner->Engaged)
        {
            return;
        }

        $destroyed = $event->theah->getCharacterById($event->characterId);
        if ($destroyed === null)
        {
            return;
        }

        if (! $this->isYourThugAtLocation($destroyed, $owner))
        {
            return;
        }

        $inDuel = (bool) $event->theah->game->globals->get(Game::IN_DUEL, false);
        $opponentMarked = ($this->opponentLethalThugId == $destroyed->Id);
        if (! $inDuel && ! $opponentMarked)
        {
            return;
        }

        $this->destroyedThugId = $destroyed->Id;
        $this->opponentLethalThugId = 0;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    private function isYourThugAtLocation(Character $character, Character $owner): bool
    {
        if (! $character->hasTrait("Thug"))
        {
            return false;
        }

        if ($character->ControllerId != $owner->ControllerId)
        {
            return false;
        }

        // WHY: Destroyed.runEventHubAfterCards — Location still city during handleEvent.
        return $character->Location == $owner->Location;
    }

    private function isOpponentSource(Theah $theah, int $sourceId, int $ownerPlayerId): bool
    {
        if ($sourceId == 0)
        {
            return false;
        }

        $source = $theah->getCardById($sourceId);
        if ($source === null)
        {
            return false;
        }

        return $source->ControllerId != 0 && $source->ControllerId != $ownerPlayerId;
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'pass')
        {
            $this->destroyedThugId = 0;
            $owner = $this->getOwningCharacter($game->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId == 'recover')
        {
            $owner = $this->getOwningCharacter($game->theah);
            $thug = $game->theah->getCardById($this->destroyedThugId);
            if ($thug === null)
            {
                $thug = $game->getCardObjectFromDb($this->destroyedThugId);
            }
            if ($owner === null || $thug === null)
            {
                $this->destroyedThugId = 0;
                $game->gamestate->nextState("done");
                return;
            }

            // Printed order: wound Desideria • Put the Thug in your hand.
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $owner->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundEvent);

            $inDuel = (bool) $game->globals->get(Game::IN_DUEL, false);
            if ($inDuel)
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} wounds ${character_inject_code} to put ${thug_inject_code} into their hand at the end of the duel.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $owner->getInjectCode(),
                    "thug_inject_code" => $thug->getInjectCode(),
                ]);

                // WHY: Leave Thug in Locker/Discard through stDuelNextPlayer death checks.
                $game->globals->set(self::pendingThugGlobalKey($owner->ControllerId), $this->destroyedThugId);
                $this->destroyedThugId = 0;
                $owner->IsUpdated = true;
            }
            else
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} wounds ${character_inject_code} to put ${thug_inject_code} into their hand.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $owner->getInjectCode(),
                    "thug_inject_code" => $thug->getInjectCode(),
                ]);

                self::moveThugFromDiscardOrLockerToHand($game, $owner->ControllerId, $thug->Id);
                $this->destroyedThugId = 0;
                $this->opponentLethalThugId = 0;
                $owner->IsUpdated = true;
            }

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }

    /**
     * Move any duel-deferred Thugs into hand. Called from stDuelEnd after IN_DUEL
     * is cleared so stDuelNextPlayer has already seen Locker/Discard death.
     */
    public static function flushPendingRecovers(Game $game): void
    {
        $players = $game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $_info)
        {
            $key = self::pendingThugGlobalKey((int) $playerId);
            $thugId = (int) $game->globals->get($key, 0);
            if ($thugId == 0)
            {
                continue;
            }

            self::moveThugFromDiscardOrLockerToHand($game, (int) $playerId, $thugId);
            $game->globals->set($key, 0);
        }
    }

    private static function moveThugFromDiscardOrLockerToHand(Game $game, int $playerId, int $thugId): void
    {
        $thug = $game->theah->getCardById($thugId);
        if ($thug === null)
        {
            $thug = $game->getCardObjectFromDb($thugId);
        }
        if ($thug === null)
        {
            return;
        }

        $lockerName = $game->getPlayerLockerName($playerId);
        $discardName = $game->getPlayerDiscardDeckName($playerId);

        // WHY: EventHub sends Brutes to discard, everyone else to locker on destroy.
        if ($thug->Location == $lockerName)
        {
            $removed = EventFactory::createCardRemovedFromLockerEvent($playerId, $thugId);
            $game->theah->queueEvent($removed);
        }
        else if ($thug->Location == $discardName)
        {
            $removed = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $thugId);
            $game->theah->queueEvent($removed);
        }
        else
        {
            return;
        }

        $addToHand = EventFactory::createCardAddedToHandEvent($playerId, $thugId);
        $game->theah->queueEvent($addToHand);
    }
}
