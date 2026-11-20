<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_05Pocketknife extends Technique
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Unequip to top of deck; adversary discards";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // WHY: Covert Technique with "adversary discards" — duel-only. Challenge path
        // would need Accept-gated discard (04017); unequip-at-challenge is out of scope.
        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $attachment = $this->getOwningAttachment($theah);
        if ($attachment === null || ! $attachment->isAttached())
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        // WHY: Covert — only at uncontrolled City locations (Corncob / Valeri / Overcoat).
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $cityLocation = $theah->getCityLocation($owner->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningAttachment($event->theah);
            $owner = $this->getOwningCharacter($event->theah);
            if ($attachment === null || $owner === null || ! $attachment->isAttached())
            {
                return;
            }

            // WHY: Dame/Lodestone sink chain, but onTop=true (printed "on top of your deck").
            // createTechniqueTransitionEvent is HIGHEST_PRIORITY, so the adversary discard
            // picker runs while still equipped (sourceId hydration), then unequip drains.
            $this->queueUnequipToTopOfDeck($event->theah, $attachment, $owner->Id);

            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary === null)
            {
                return;
            }

            $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
            if (count($hand) > 0)
            {
                // WHY: sourceId = attachment — FrameworkActionsTrait hydrates source and
                // getTechniqueById; character sourceId would hide an attachment-hosted technique.
                $transition = EventFactory::createTechniqueTransitionEvent(
                    $adversary->ControllerId,
                    $attachment->Id,
                    "05Pocketknife",
                    $this->Id
                );
                $event->theah->queueEvent($transition);
            }
            else
            {
                // WHY: Checklist 9 — empty hand must not dead-end the duel.
                $event->theah->game->notify->all("message", '${technique_inject_code}: ${player_name} has no cards to discard.', [
                    "technique_inject_code" => $attachment->getInjectCode(),
                    "player_name" => $event->theah->game->getPlayerNameById($adversary->ControllerId),
                ]);
            }
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state != States::DUEL_CHOOSE_TECHNIQUE_05POCKETKNIFE)
        {
            return;
        }

        $card = $game->getCardObjectFromDb($id);
        if ($card === null)
        {
            throw new UserException("Card not found");
        }

        $playerId = $game->getActivePlayerId();
        if ($card->ControllerId != $playerId)
        {
            throw new UserException("You do not control this card");
        }

        if ($card->Location != Game::LOCATION_HAND)
        {
            throw new UserException("Card not in your hand");
        }

        $attachment = $this->getOwningCard($game->theah);
        $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
            $card->OwnerId,
            $card->Id,
            $attachment !== null ? $attachment->Id : 0,
            $asPayment = false,
            $asPlayed = false,
            $asEffect = true
        );
        $game->theah->queueEvent($discardEvent);

        $game->gamestate->nextState();
    }

    private function queueUnequipToTopOfDeck(Theah $theah, Attachment $attachment, int $characterId): void
    {
        $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
            $attachment->ControllerId,
            $characterId,
            $attachment->Id
        );
        $theah->queueEvent($unequipEvent);

        $removedEvent = EventFactory::createCardRemovedFromPlayEvent(
            $attachment->ControllerId,
            $attachment->Id,
            $attachment->Location
        );
        $theah->queueEvent($removedEvent);

        // WHY: OwnerId — faction deck belongs to the card's owner (Dame sink uses OwnerId).
        $deckEvent = EventFactory::createCardAddedToFactionDeckEvent(
            $attachment->OwnerId,
            $attachment->Id,
            true
        );
        $theah->queueEvent($deckEvent);
    }
}
