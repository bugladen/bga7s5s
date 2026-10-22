<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Sniper extends CardReaction
{
    // WHY: Buttons render later, after the issued event is gone. Stash both ids.
    private int $ChallengerId = 0;
    private int $DefenderId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Wound Challenger or Challenged";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may wound the challenger or the challenged character: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        foreach ([$this->ChallengerId, $this->DefenderId] as $characterId)
        {
            $character = $this->woundableCharacter($theah, $characterId);
            if ($character === null)
            {
                continue;
            }

            $array[] = $this->createButtonProperty($theah->game, 'Wound ' . $character->Name, "wound-{$character->Id}");
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventChallengeIssued) || $event->canceled || ! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! $event->theah->cardInPlay($owner))
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        $challenger = $event->theah->getCharacterById($event->challengerId);
        if ($challenger === null)
        {
            return;
        }

        // WHY: "this location or an adjacent one". Same location includes Home.
        // Adjacent is the city list without Home, matching other adjacent-location
        // reactions: a challenge at Home is in range only when Eleanor is at Home.
        if (! $this->challengeIsInRange($event->theah, $owner, $challenger->Location))
        {
            return;
        }

        $defender = $event->theah->getCharacterById($event->defenderId);
        $challengerOk = $this->woundableCharacter($event->theah, $challenger->Id) !== null;
        $defenderOk = $defender !== null && $this->woundableCharacter($event->theah, $defender->Id) !== null;
        if (! $challengerOk && ! $defenderOk)
        {
            return;
        }

        $this->ChallengerId = $challenger->Id;
        $this->DefenderId = $defender?->Id ?? 0;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if (str_starts_with($reactionId, 'wound-'))
        {
            $owner = $this->getOwningCharacter($game->theah);
            $targetId = (int) substr($reactionId, strlen('wound-'));
            $target = $this->woundableCharacter($game->theah, $targetId);

            if ($owner === null || $target === null || ($targetId !== $this->ChallengerId && $targetId !== $this->DefenderId))
            {
                throw new UserException("Choose the challenger or the challenged character");
            }

            $game->notify->all("message", '${character_inject_code}: ${player_name} wounds ${target_inject_code}.', [
                "character_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $target->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->eventCheck($woundEvent);
            $game->theah->queueEvent($woundEvent);

            $this->setUsed($game->theah, true);
            $this->ChallengerId = 0;
            $this->DefenderId = 0;
        }

        $game->gamestate->nextState("done");
    }

    private function challengeIsInRange(Theah $theah, Character $owner, string $challengeLocation): bool
    {
        if ($challengeLocation === $owner->Location)
        {
            return true;
        }

        $adjacent = $theah->getAdjacentCityLocations($owner->Location, false);
        return in_array($challengeLocation, $adjacent, true);
    }

    private function woundableCharacter(Theah $theah, int $characterId): ?Character
    {
        if ($characterId <= 0)
        {
            return null;
        }

        $character = $theah->getCharacterById($characterId);
        if ($character === null || $theah->game->characterIsInDiscardOrLocker($character))
        {
            return null;
        }

        return $character;
    }
}
