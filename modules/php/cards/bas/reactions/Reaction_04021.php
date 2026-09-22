<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04021 extends CardReaction
{
    // WHY public: must survive serialize across the reaction prompt round-trip.
    public int $engagedMusketeerId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde your other Musketeer after an opponent engages them");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may En Garde your Musketeer who was engaged by an opponent\'s effect: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $musketeer = $theah->getCharacterById($this->engagedMusketeerId);
        if ($musketeer !== null)
        {
            $array[] = $this->createButtonProperty(
                $theah->game,
                sprintf($theah->game->translate('En Garde %s'), $musketeer->Name),
                'enGarde'
            );
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    /**
     * Mirror Reaction_03031: opponent ability = source card (or in-play action owner)
     * controlled by a different non-zero player. sourceId==0 (framework auto-engage)
     * is not an "opponent's effect."
     */
    private function isOpponentEffect(Theah $theah, int $sourceId, string $abilityId, int $ownerPlayerId): bool
    {
        $source = $theah->getCardById($sourceId);
        if ($source)
        {
            return $source->ControllerId != $ownerPlayerId && $source->ControllerId != 0;
        }

        $action = $theah->getInPlayActionById($abilityId);
        if ($action && $action instanceof ICardAbility)
        {
            $owningCard = $action->getOwningCard($theah);
            return $owningCard !== null
                && $owningCard->ControllerId != $ownerPlayerId
                && $owningCard->ControllerId != 0;
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Unlabelled "After … may …" → Continuous Reaction (Ekaterina / Angeline).
        // Trigger is EventCardEngaged from an opponent's effect on your other Musketeer
        // at Aimée's location — not Challenge auto-engage (sourceId 0).
        if (! ($event instanceof EventCardEngaged)) return;
        if ($event->canceled) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;
        if ($owner->ControllerId == 0) return;
        if ($event->theah->game->characterIsInDiscardOrLocker($owner)) return;

        if (! $this->isOpponentEffect($event->theah, $event->sourceId, $event->abilityId, $owner->ControllerId))
        {
            return;
        }

        $musketeer = $event->theah->getCharacterById($event->cardId);
        if ($musketeer === null) return;
        if ($musketeer->Id == $owner->Id) return;
        if ($musketeer->ControllerId != $owner->ControllerId) return;
        if ($musketeer->Location != $owner->Location) return;
        if (! $musketeer->hasTrait("Musketeer")) return;

        $this->engagedMusketeerId = $musketeer->Id;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'enGarde')
        {
            $owner = $this->getOwningCharacter($game->theah);
            $musketeer = $game->theah->getCharacterById($this->engagedMusketeerId);

            if ($owner !== null
                && $musketeer instanceof Character
                && $musketeer->ControllerId == $owner->ControllerId
                && $musketeer->Location == $owner->Location
                && $musketeer->hasTrait("Musketeer")
                && $musketeer->Id != $owner->Id
                && $musketeer->Engaged)
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} En Gardes ${character_inject_code} after an opponent engaged them.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $musketeer->getInjectCode(),
                ]);

                $engardeEvent = EventFactory::createCardEngardedEvent(
                    $owner->ControllerId,
                    $musketeer->Id,
                    $owner->Id,
                    $this->Id
                );
                $game->theah->queueEvent($engardeEvent);
            }

            // Continuous Reaction: intentionally do NOT call $this->setUsed(true).
            // Fires again whenever an opponent's effect engages another Musketeer here.
        }

        $this->engagedMusketeerId = 0;
        $owner = $this->getOwningCharacter($game->theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
