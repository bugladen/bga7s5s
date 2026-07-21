<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03044 extends AttachmentReaction
{
    // WHY public: match Reaction_01047 / 01146b — nested reaction state must survive serialize/DB round-trips reliably across multi-stage playerReaction requests.
    // '' idle, 'offer' (cloak controller Engage/Pass), 'threat' (adversary discard or accept cancel)
    public string $stage = '';
    public string $TechniqueId = '';
    public string $ManeuverId = '';
    public int $adversaryPlayerId = 0;
    public int $activatingPlayerId = 0;
    public int $actorId = 0;
    public int $adversaryCharacterId = 0;
    public bool $techniqueWasMain = false;
    // WHY: Engage deletes pending Resolve/Calculate immediately (cancel-first). true while waiting on threat so Accept Cancel can fire *Canceled and discard can restore.
    public bool $pendingCancel = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Adversary's Maneuver or Technique unless they Discard");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->stage === 'offer')
        {
            return $base . $theah->game->translate('${you} may engage Torres Cloak to cancel the adversary\'s Maneuver or Technique unless they discard a card: ');
        }

        if ($this->stage === 'threat')
        {
            return $base . $theah->game->translate('${you} must discard a card to keep your Maneuver or Technique, or accept the cancel: ');
        }

        return $base;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        if ($this->stage === 'offer')
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Engage'), 'engage');
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        }

        if ($this->stage === 'threat')
        {
            $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->adversaryPlayerId);
            foreach ($hand as $card)
            {
                $array[] = $this->createButtonProperty($theah->game, $card->Name, 'discardHand-' . $card->Id);
            }
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Accept Cancel'), 'acceptCancel');
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! $this->isAvailable())
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        if ($owner == null || $owner->Engaged)
        {
            return;
        }

        if (! $event->theah->game->globals->get(Game::IN_DUEL, false))
        {
            return;
        }

        if ($event instanceof EventTechniqueActivated)
        {
            if (! $this->isAdversaryActivating($event->theah, $event->playerId))
            {
                return;
            }

            $this->TechniqueId = $event->techniqueId;
            $this->ManeuverId = '';
            $this->queueOffer($event, $owner);
            return;
        }

        if ($event instanceof EventManeuverActivated)
        {
            if (! $this->isAdversaryActivating($event->theah, $event->playerId))
            {
                return;
            }

            $this->ManeuverId = $event->maneuverId;
            $this->TechniqueId = '';
            $this->queueOffer($event, $owner);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($this->stage === 'offer')
        {
            if ($reactionId === 'engage')
            {
                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);

                // WHY: Do NOT setUsed here — runEvents skips later reaction transitions when
                // !isAvailable(). setUsed belongs in finalizeAfterEngage (mirror Reaction_03007).

                $abilityName = $this->getPendingAbilityName($game);
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} engages ${owner_inject_code} to cancel ${ability} unless ${opponent_name} discards a card.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "owner_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "opponent_name" => $game->getPlayerNameById($this->adversaryPlayerId),
                    "ability" => $abilityName,
                ]);

                // WHY cancel-first: delete Resolve/Calculate now so they cannot fire while the
                // adversary decides. Discard re-queues them; Accept Cancel confirms with *Canceled.
                // Leaving them queued until Accept Cancel raced Technique_01093 into duelChooseTechnique_01093.
                $this->stripPendingAbilityEffects($game);
                $this->pendingCancel = true;

                $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->adversaryPlayerId);
                if (count($hand) === 0)
                {
                    $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${opponent_name} has no cards in hand to discard, so ${ability} is canceled.'), [
                        "reaction_inject_code" => $owner->getInjectCode(),
                        "opponent_name" => $game->getPlayerNameById($this->adversaryPlayerId),
                        "ability" => $abilityName,
                    ]);
                    $this->confirmCancel($game, $owner);
                    $this->finalizeAfterEngage($game, $owner);
                    $game->gamestate->nextState("done");
                    return;
                }

                $this->stage = 'threat';
                $owner->IsUpdated = true;

                $transition = EventFactory::createReactionTransitionEvent($this->adversaryPlayerId, $owner->Id, $this->Id);
                // WHY: stay ahead of any remaining MEDIUM events (engage notify chain, etc.)
                $transition->priority = Event::HIGH_PRIORITY;
                $game->theah->queueEvent($transition);

                $game->gamestate->nextState("done");
                return;
            }

            $this->resetStage();
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        if ($this->stage === 'threat')
        {
            if (str_starts_with($reactionId, 'discardHand-'))
            {
                if ((int) $game->getActivePlayerId() !== $this->adversaryPlayerId)
                {
                    $game->gamestate->nextState("done");
                    return;
                }

                $cardId = (int) substr($reactionId, strlen('discardHand-'));
                $handIds = array_map(fn($c) => $c->Id, $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->adversaryPlayerId));
                if (! in_array($cardId, $handIds, true))
                {
                    throw new UserException($game->translate("Selected card is not in your hand."));
                }

                $discarded = $game->getCardObjectFromDb($cardId);
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} discards ${card_inject_code} to keep their Maneuver or Technique.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($this->adversaryPlayerId),
                    "card_inject_code" => $discarded->getInjectCode(),
                ]);

                $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                    $this->adversaryPlayerId,
                    $cardId,
                    $owner->Id,
                    false,
                    false,
                    true
                );
                $game->theah->queueEvent($discardEvent);

                $this->restorePendingAbility($game);
                $this->finalizeAfterEngage($game, $owner);
                $game->gamestate->nextState("done");
                return;
            }

            if ($reactionId === 'acceptCancel')
            {
                $this->confirmCancel($game, $owner);
                $this->finalizeAfterEngage($game, $owner);
                $game->gamestate->nextState("done");
                return;
            }
        }

        $game->gamestate->nextState("done");
    }

    private function finalizeAfterEngage(Game $game, Card $owner): void
    {
        $this->setUsed($game->theah, true);
        $this->resetStage();
        $owner->IsUpdated = true;
    }

    private function isAdversaryActivating(Theah $theah, int $activatingPlayerId): bool
    {
        $owningCharacter = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owningCharacter == null || $actor == null)
        {
            return false;
        }

        // WHY: equipped participant must be the actor's duel adversary; activator must be the actor's controller.
        // Reaction_01047 compared ControllerId to character ids — that mixes id spaces; do not copy it.
        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        if ($owningCharacter->Id != $adversaryId)
        {
            return false;
        }

        return $activatingPlayerId == $actor->ControllerId;
    }

    private function queueOffer(Event $event, Card $owner): void
    {
        $actor = $event->theah->getDuelRoundActor();
        $this->adversaryPlayerId = $actor->ControllerId;
        $this->activatingPlayerId = $event->playerId;
        $this->actorId = $actor->Id;
        $this->adversaryCharacterId = $event->theah->getDuelOpponentId($actor->Id);
        $this->techniqueWasMain = (bool) $event->theah->game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN, false);
        $this->stage = 'offer';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        // WHY: HIGH_PRIORITY so this interrupt runs before MEDIUM-priority ResolveTechnique/ResolveManeuver events
        $transition->priority = Event::HIGH_PRIORITY;
        $event->theah->queueEvent($transition);
    }

    private function getPendingAbilityName(Game $game): string
    {
        if ($this->TechniqueId != '')
        {
            $technique = $game->theah->getTechniqueById($this->TechniqueId);
            return $technique ? $technique->Name : $this->TechniqueId;
        }

        if ($this->ManeuverId != '')
        {
            $maneuver = $game->theah->getManeuverById($this->ManeuverId);
            return $maneuver ? $maneuver->Name : $this->ManeuverId;
        }

        return '';
    }

    private function stripPendingAbilityEffects(Game $game): void
    {
        if ($this->TechniqueId != '')
        {
            $game->globals->delete(Game::CHOSEN_TECHNIQUE);
            $game->globals->delete(Game::CHOSEN_TECHNIQUE_IS_MAIN);
            $game->theah->deleteTechniqueEvents($this->TechniqueId);
        }

        if ($this->ManeuverId != '')
        {
            $game->globals->delete(Game::CHOSEN_MANEUVER);
            $game->theah->deleteManeuverEvents($this->ManeuverId);
        }
    }

    private function confirmCancel(Game $game, Card $owner): void
    {
        if ($this->TechniqueId != '')
        {
            $technique = $game->theah->getTechniqueById($this->TechniqueId);
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} canceled Technique: [${technique}]'), [
                "i18n" => ["technique"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "technique" => $technique ? $technique->Name : $this->TechniqueId,
            ]);

            $canceledEvent = EventFactory::createTechniqueCanceledEvent($owner->ControllerId, $this->TechniqueId);
            $game->theah->queueEvent($canceledEvent);
        }

        if ($this->ManeuverId != '')
        {
            $maneuver = $game->theah->getManeuverById($this->ManeuverId);
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} canceled Maneuver: [${maneuver}]'), [
                "i18n" => ["maneuver"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "maneuver" => $maneuver ? $maneuver->Name : $this->ManeuverId,
            ]);

            $canceledEvent = EventFactory::createManeuverCanceledEvent($owner->ControllerId, $this->ManeuverId);
            $game->theah->queueEvent($canceledEvent);
        }

        $this->pendingCancel = false;
    }

    private function restorePendingAbility(Game $game): void
    {
        if ($this->TechniqueId != '')
        {
            $game->globals->set(Game::CHOSEN_TECHNIQUE, $this->TechniqueId);
            $game->globals->set(Game::CHOSEN_TECHNIQUE_IS_MAIN, $this->techniqueWasMain);

            $resolveEvent = EventFactory::createResolveTechniqueEvent(
                $this->activatingPlayerId,
                $this->actorId,
                $this->adversaryCharacterId,
                $this->TechniqueId
            );
            $game->theah->queueEvent($resolveEvent);

            $valuesEvent = EventFactory::createDuelCalculateTechniqueValuesEvent(
                $this->actorId,
                $this->adversaryCharacterId,
                $this->TechniqueId
            );
            $game->theah->queueEvent($valuesEvent);
        }

        if ($this->ManeuverId != '')
        {
            $game->globals->set(Game::CHOSEN_MANEUVER, $this->ManeuverId);

            $resolveEvent = EventFactory::createResolveManeuverEvent(
                $this->activatingPlayerId,
                $this->adversaryCharacterId,
                $this->ManeuverId
            );
            $game->theah->queueEvent($resolveEvent);

            $valuesEvent = EventFactory::createDuelCalculateManeuverValuesEvent(
                $this->actorId,
                $this->adversaryCharacterId,
                $this->ManeuverId
            );
            $game->theah->queueEvent($valuesEvent);
        }

        $this->pendingCancel = false;
    }

    private function resetStage(): void
    {
        $this->stage = '';
        $this->TechniqueId = '';
        $this->ManeuverId = '';
        $this->adversaryPlayerId = 0;
        $this->activatingPlayerId = 0;
        $this->actorId = 0;
        $this->adversaryCharacterId = 0;
        $this->techniqueWasMain = false;
        $this->pendingCancel = false;
    }
}
