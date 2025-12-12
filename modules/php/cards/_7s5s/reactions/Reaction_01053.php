<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01053 extends CancelReaction
{
    private int $SourceId = 0;
    private int $TargetId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Sorcerer Ability Targeting a Card");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to cancel Sorcerer Ability Targeting Card: ');        
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $target = $theah->getCardById($this->TargetId);
        $performers = $theah->getCharactersAtLocationbyPlayerId($target->Location, $owner->ControllerId);

        foreach ($performers as $performer)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Wound and Cancel Sorcerer Ability with %s'), $performer->Name), "cancel-{$performer->Id}");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventSorcererAbilityStart && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            $source = $event->theah->getCardById($event->sourceId);
            $ability = $source->getAbilityById($event->abilityId);
            if ($event->targetId != 0)
            {
                $target = $event->theah->getCardById($event->targetId);
            
                if ($ability instanceof IAbilityThatTargetsCards && $target->Location != Game::LOCATION_PLAYER_HOME)
                {
                    $performers = $event->theah->getCharactersAtLocationbyPlayerId($target->Location, $owner->ControllerId);
                    if (count($performers) > 0)
                    {
                        $this->TargetId = $event->targetId;
                        $this->SourceId = $event->sourceId;
                        $owner->IsUpdated = true;
    
                        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->stackEvent($transition);
                    }
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            if ($event->reactionId != 'decline')
            {
                $game = $event->theah->game;
                $performerId = str_replace("cancel-", "", $event->reactionId);
                $performer = $game->theah->getCharacterById($performerId);
    
                $target = $game->theah->getCardById($this->TargetId);
                $owner = $game->theah->getCardById($this->OwnerId);
    
                $game->theah->deleteEventsTargetingCard($target->Id);
                $game->theah->deleteTransitionEventsBySourceId($this->SourceId);
    
                $event = EventFactory::createCharacterWoundedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($event);
    
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to cancel the Sorcerer Ability Targeting ${card_inject_code}.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getActivePlayerName(),
                    "card_inject_code" => $target->getInjectCode(),
                ]);
    
                $this->setUsed($game->theah, true);
            }
        }        
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'decline')
        {
            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        $game->gamestate->nextState("done");
    }
}