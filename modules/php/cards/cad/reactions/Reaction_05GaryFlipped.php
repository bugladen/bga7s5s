<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05GaryFlipped extends CardReaction
{
    // WHY: "Rena's adversary" is the other duel seat (challenger/defender), not any
    // opposing character. Round actor/opponent can be the corpse by destroy time, so
    // the seats are the stable ids (same lookup as Victorious / Glorious).
    // "This location" is Rena's location when the adversary is destroyed. Stash it:
    // the click is a later request, and the text does not say "uncontrolled", so an
    // opponent-controlled city location is still a legal claim. Home and Indomitable
    // Will fail canLocationBeClaimedBy and never open the window.
    private string $location = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Claim this location when Rena's adversary is destroyed";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may claim this location: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, sprintf('Claim %s', $this->location), 'claim');
        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCharacterDestroyed) || ! $this->isAvailable())
        {
            return;
        }

        if (! $event->theah->game->globals->get(Game::IN_DUEL))
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! $owner->isControlled())
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        $challengerId = (int) $event->theah->getDuelChallengerId();
        $defenderId = (int) $event->theah->getDuelDefenderId();
        $destroyedId = (int) $event->characterId;

        $renasAdversaryDestroyed = ($owner->Id == $challengerId && $destroyedId == $defenderId)
            || ($owner->Id == $defenderId && $destroyedId == $challengerId);
        if (! $renasAdversaryDestroyed)
        {
            return;
        }

        if (! $event->theah->canLocationBeClaimedBy($owner->ControllerId, $owner->Location))
        {
            return;
        }

        $this->location = $owner->Location;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        $owner = $this->getOwningCharacter($game->theah);

        // WHY before parent: keep Claim/Pass open. UserException rolls the request back
        // so the player can Pass instead of the window closing on a claim that went illegal.
        if ($reactionId == 'claim'
            && $this->location != ''
            && ($owner === null || ! $game->theah->canLocationBeClaimedBy($owner->ControllerId, $this->location)))
        {
            throw new UserException(sprintf('%s cannot be claimed.', $this->location));
        }

        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'claim' && $this->location != '' && $owner !== null)
        {
            $claimEvent = EventFactory::createLocationClaimedEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->location
            );
            $game->theah->queueEvent($claimEvent);

            $game->notify->all("message", '${reaction_inject_code}: ${player_name} used Reaction to Claim ${location_name}.', [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "location_name" => $this->location,
            ]);

            $this->setUsed($game->theah, true);
        }

        $this->location = '';
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
