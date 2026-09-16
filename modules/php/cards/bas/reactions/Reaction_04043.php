<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04043 extends CardReaction
{
    private string $location = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Claim uncontrolled location at High Drama End");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may claim Sango\'s uncontrolled location (she is en garde): ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty(
            $theah->game,
            sprintf($theah->game->translate('Claim %s'), $this->location),
            'claim'
        );
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventHighDramaPhaseEnd) || ! $this->isAvailable())
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

        // En Garde Reaction — precondition, not an Engage cost.
        if ($owner->Engaged)
        {
            return;
        }

        // Claim only applies to City locations; Home is never uncontrolled/claimable.
        if (! $event->theah->cardInCity($owner))
        {
            return;
        }

        // WHY both gates: text requires uncontrolled (controller == 0). CanBeClaimed alone
        // can be false under Indomitable Will even when uncontrolled, and can be true while
        // already controlled for some effects — so check controller explicitly.
        if ($event->theah->game->getControllerForLocation($owner->Location) != 0)
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
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'claim' && $this->location != '' && $owner !== null)
        {
            // Re-check En Garde + uncontrolled + claimable (state may have changed).
            if ($owner->Engaged
                || $game->getControllerForLocation($this->location) != 0
                || ! $game->theah->canLocationBeClaimedBy($owner->ControllerId, $this->location))
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${location_name} cannot be claimed.'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "location_name" => $this->location,
                ]);
            }
            else
            {
                $claimEvent = EventFactory::createLocationClaimedEvent(
                    $owner->ControllerId,
                    $owner->Id,
                    $this->location
                );
                $game->theah->queueEvent($claimEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to Claim ${location_name}.'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "location_name" => $this->location,
                ]);

                $this->setUsed($game->theah, true);
            }
        }

        $this->location = '';
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
