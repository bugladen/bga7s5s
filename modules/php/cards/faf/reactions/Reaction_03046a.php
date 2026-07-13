<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03046a extends RiskReaction
{
    private int $performerId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Your Intervening Duelist");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may play this Risk to En Garde your intervening Duelist: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Passionate'), 'use');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterIntervened && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null) return;
            if (! ($owner->Location == Game::LOCATION_HAND)) return;

            // WHY: "After your performer intervenes" — intervening player owns the Risk; intervener IS the Duelist performer.
            if ($event->playerId != $owner->ControllerId) return;

            $intervener = $event->theah->getCharacterById($event->newTargetId);
            if ($intervener === null) return;
            if (! $intervener->hasTrait("Duelist")) return;

            $this->performerId = $intervener->Id;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performer = $event->theah->getCharacterById($this->performerId);
            if ($owner === null || $performer === null) return;

            // WHY: Intervene queues Engaged after Intervened; by pay time they should be Engaged.
            // Odette/Rena deferrals leave Engaged=false — engarde is a no-op then, still spent the Risk.
            if ($performer->Engaged)
            {
                $engardeEvent = EventFactory::createCardEngardedEvent($owner->ControllerId, $performer->Id, $owner->Id, $this->Id);
                $event->theah->queueEvent($engardeEvent);
            }

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to En Garde ${character_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $performer->getInjectCode(),
            ]);

            $this->setUsed($event->theah, true);
            $this->performerId = 0;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'use')
        {
            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($payEvent);

            $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($payTransition);
        }
        else
        {
            $this->performerId = 0;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
