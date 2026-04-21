<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02059 extends RiskReaction
{
    private ?EventCharacterBeingWounded $savedWoundEvent = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ignore Opponent's Wound");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may ignore a wound from an opponent\'s ability: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Ignore Wound'), 'ignoreWound');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterBeingWounded && !$event->canceled && $this->isAvailable() && $this->savedWoundEvent === null)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND && $event->abilityId != '')
            {
                $character = $event->theah->getCharacterById($event->characterId);
                if ($character && $character->ControllerId == $owner->ControllerId)
                {
                    $source = $event->theah->getCardById($event->sourceId);
                    if ($source)
                    {
                        $ability = $source->getAbilityById($event->abilityId);
                        if ($ability)
                        {
                            $abilityOwner = $ability->getOwningCard($event->theah);
                            if ($abilityOwner && $abilityOwner->ControllerId != $owner->ControllerId)
                            {
                                $this->savedWoundEvent = clone $event;
                                unset($this->savedWoundEvent->theah);
                                $event->canceled = true;
                                $owner->IsUpdated = true;

                                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                                $event->theah->queueEvent($transition);
                            }
                        }
                    }
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($game->theah);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to ignore the wound.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $this->savedWoundEvent = null;
            $this->setUsed($game->theah, true);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'ignoreWound')
        {
            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        if ($reactionId == 'decline')
        {
            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;

            if ($this->savedWoundEvent)
            {
                $game->theah->queueEvent($this->savedWoundEvent);
                $this->savedWoundEvent = null;
            }
        }

        $game->gamestate->nextState("done");
    }
}
