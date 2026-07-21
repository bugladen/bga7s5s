<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03012 extends RiskReaction implements ISorcererAbility
{
    private int $intervenerId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Change Challenge to Influence");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may play this Risk so the challenge becomes an [Influence] challenge: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Subtle'), 'use');
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

            if ($event->playerId != $owner->ControllerId) return;

            $intervener = $event->theah->getCharacterById($event->newTargetId);
            if ($intervener === null) return;
            if (! $intervener->hasTrait("Strega")) return;

            $this->intervenerId = $intervener->Id;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $intervener = $event->theah->getCharacterById($this->intervenerId);
            if ($owner === null || $intervener === null) return;

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $intervener->Id);
            $event->theah->queueEvent($sorceryStartEvent);

            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $intervener->Id);
            $event->theah->queueEvent($sorceryPlayedEvent);

            $game->notify->all("duelStatChanged", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction. The challenge becomes an [Influence] challenge.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "duelStat" => $game->translate("Influence"),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $this->setUsed($event->theah, true);
            $this->intervenerId = 0;
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
            $this->intervenerId = 0;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
