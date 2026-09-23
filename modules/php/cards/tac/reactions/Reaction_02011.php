<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01156;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01191;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01157;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02054;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02011 extends CardReaction
{
    public int $sourceId;
    public string $sourceAbilityId;
    public string $sourceTargetLocation = '';

    public Array $copiedActions = [];
    public Array $copiedManeuvers = [];
    public Array $copiedTechniques = [];
    public Array $copiedReactions = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Copy Ranged Ability");
        $this->sourceId = 0;
        $this->sourceAbilityId = '';
        $this->sourceTargetLocation = '';
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Copy Ranged Ability Just Played: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
        foreach ($hand as $card)
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Discard ') . $theah->game->translate($card->Name), "copyRangedAbility-$card->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventRangedAbilityPlayed && $this->isAvailable())
        {
            $katain = $this->getOwningCharacter($event->theah);
            if ($event->performerId == $katain->Id)
            {
                $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $katain->ControllerId);
                if (count($hand) > 0)
                {
                    $this->sourceId = $event->sourceId;
                    $this->sourceAbilityId = $event->abilityId;
                    $this->sourceTargetLocation = $event->targetLocation;
                    $katain->IsUpdated = true;

                    $reactionTransition = EventFactory::createReactionTransitionEvent($katain->ControllerId, $katain->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransition);
                }
            }
        }

        if ($event instanceof EventPlayerTurnEnd)
        {
            $katain = $this->getOwningCharacter($event->theah);
            foreach ($this->copiedActions as $action)
            {
                if ($katain instanceof IHasActions)
                {
                    $katain->removeAction($action, $event->theah->game);
                }
            }
            $this->copiedActions = [];

            foreach ($this->copiedManeuvers as $maneuver)
            {
                if ($katain instanceof IHasManeuvers)
                {
                    $katain->removeManeuver($maneuver, $event->theah->game);
                }
            }
            $this->copiedManeuvers = [];

            foreach ($this->copiedTechniques as $technique)
            {
                if ($katain instanceof IHasTechniques)
                {
                    $katain->removeTechnique($technique, $event->theah->game);
                }
            }
            $this->copiedTechniques = [];

            foreach ($this->copiedReactions as $reaction)
            {
                if ($katain instanceof IHasReactions)
                {
                    $katain->removeReaction($reaction, $event->theah->game);
                }
            }
            $this->copiedReactions = [];
            $katain->IsUpdated = true;
        }
    }

    private function announceReaction(Game $game, ICardAbility $ability): void
    {
        $game->notify->all("message", clienttranslate('${character_inject_code}: ${player_name} used Reaction to copy the Ranged Ability [${ability_name}]'), [
            "i18n" => ["ability_name"],
            "character_inject_code" => $this->getOwningCharacter($game->theah)->getInjectCode(),
            "player_name" => $game->getActivePlayerName(),
            "ability_name" => $ability->Name,
        ]);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);
        
        if ($reactionId != "decline")
        {
            $source = $game->theah->getCardById($this->sourceId);
            $ability = $source->getAbilityById($this->sourceAbilityId);
            $katain = $this->getOwningCharacter($game->theah);

            $copyAction = false;
            $copyManeuver = false;
            $copyTechnique = false;
            $copyReaction = false;
            $action = null;
            $maneuver = null;
            $technique = null;
            $reaction = null;

            // Polished Flintlock
            if ($ability instanceof Action_01049)
            {
                $copyAction = true;
                $action = new Action_01049();
                $action->setOwnerId($katain->Id);
                $action->IsEffectsOnlyCopy = true;
                // WHY keep originalAttachmentId: City Action effect includes "engage this
                // card" when wounding — that is effect text, not a cost. Point engage at
                // the real flintlock (owner is Katain). Contrast Technique_01049 below.
                $action->originalAttachmentId = $source->Id;
                if ($katain instanceof IHasActions) $katain->addAction($action, $game);
            }

            // Polished Flintlock 
            if ($ability instanceof Technique_01049)
            {
                $copyTechnique = true;
                $technique = new Technique_01049();
                $technique->setOwnerId($katain->Id);
                // WHY no originalAttachmentId: Technique cost is "Engage this card • …".
                // IsEffectsOnlyCopy skips that engage in Technique_01049::handleEvent.
                $technique->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasTechniques) $katain->addTechnique($technique, $game);
            }

            // Last Word
            if ($ability instanceof Action_01055)
            {
                $copyAction = true;
                $action = new Action_01055();
                $action->setOwnerId($katain->Id);
                $action->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasActions) $katain->addAction($action, $game);
            }
            if ($ability instanceof Maneuver_01055)
            {
                $copyManeuver = true;
                $maneuver = new Maneuver_01055();
                $maneuver->setOwnerId($katain->Id);
                $maneuver->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasManeuvers) $katain->addManeuver($maneuver, $game);
            }

            // Precision
            if ($ability instanceof Maneuver_01057)
            {
                $copyManeuver = true;
                $maneuver = new Maneuver_01057();
                $maneuver->setOwnerId($katain->Id);
                $maneuver->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasManeuvers) $katain->addManeuver($maneuver, $game);
            }

            // Matchlock Musket
            if ($ability instanceof Action_01156)
            {
                $copyAction = true;
                $action = new Action_01156();
                $action->setOwnerId($katain->Id);
                $action->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasActions) $katain->addAction($action, $game);
            }

            // Throwing Knife
            if ($ability instanceof Technique_01157)
            {
                $copyTechnique = true;
                $technique = new Technique_01157();
                $technique->setOwnerId($katain->Id);
                $technique->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasTechniques) $katain->addTechnique($technique, $game);
            }

            // Duckfoot Pistol
            if ($ability instanceof Action_01191)
            {
                $copyAction = true;
                $action = new Action_01191();
                $action->setOwnerId($katain->Id);
                $action->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasActions) $katain->addAction($action, $game);
            }

            //Concealed Flintlock
            if ($ability instanceof Technique_02054)
            {
                $copyTechnique = true;
                $technique = new Technique_02054();
                $technique->setOwnerId($katain->Id);
                $technique->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasTechniques) $katain->addTechnique($technique, $game);
            }

            // Jägerarmbrust
            if ($ability instanceof Technique_04017)
            {
                $copyTechnique = true;
                $technique = new Technique_04017();
                $technique->setOwnerId($katain->Id);
                $technique->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasTechniques) $katain->addTechnique($technique, $game);
            }

            // Vantage Point — first Risk Reaction on Katain's allow-list.
            // WHY host on Katain + beginEffectsCopy: effects are multi-stage reaction buttons
            // (opponent −1, then engage-or-wound). Skip wealth pay (IsEffectsOnlyCopy / no pay state).
            // Pressure location comes from the RangedAbilityPlayed event (pressured site).
            if ($ability instanceof Reaction_04020)
            {
                $copyReaction = true;
                $reaction = new Reaction_04020();
                $reaction->setOwnerId($katain->Id);
                $reaction->IsEffectsOnlyCopy = true;
                if ($katain instanceof IHasReactions) $katain->addReaction($reaction, $game);
            }

            if ($copyAction || $copyManeuver || $copyTechnique || $copyReaction)
            {
                $cardId = str_replace("copyRangedAbility-", "", $reactionId);
                $discardEvent = EventFactory::createCardDiscardedFromHandEvent($katain->ControllerId, $cardId, $katain->Id);
                $game->theah->queueEvent($discardEvent);
            }

            //If it was an action, check if it is available to copy
            if ($copyAction)
            {
                $this->copiedActions[] = $action;
                $katain->IsUpdated = true;

                $game->globals->set(Game::ABNORMAL_FLOW, true);
                $transition = EventFactory::createActionTriggeredEvent($katain->ControllerId, $katain->Id, $katain->Id, $action->Id);
                $game->theah->queueEvent($transition);
    
                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }

            //If it was a maneuver, check if it is available to copy
            if ($copyManeuver)
            {
                $this->copiedManeuvers[] = $maneuver;
                $katain->IsUpdated = true;

                $adversaryId = $game->theah->getDuelOpponentId($katain->Id);

                $resolveEvent = EventFactory::createResolveManeuverEvent($katain->ControllerId, $adversaryId, $maneuver->Id);
                $game->theah->eventCheck($resolveEvent);
                $game->theah->queueEvent($resolveEvent);
        
                $threatEvent = EventFactory::createDuelCalculateManeuverValuesEvent($katain->Id, $adversaryId, $maneuver->Id);
                $game->theah->eventCheck($threatEvent);
                $game->theah->queueEvent($threatEvent);

                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }

            //If it was a technique, check if it is available to copy
            if ($copyTechnique)
            {
                $this->copiedTechniques[] = $technique;
                $katain->IsUpdated = true;
    
                $adversaryId = $game->theah->getDuelOpponentId($katain->Id);
    
                $resolveEvent = EventFactory::createResolveTechniqueEvent($katain->ControllerId, $katain->Id, $adversaryId, $technique->Id);
                $game->theah->eventCheck($resolveEvent);
                $game->theah->queueEvent($resolveEvent);
        
                $threatEvent = EventFactory::createDuelCalculateTechniqueValuesEvent($katain->Id, $adversaryId, $technique->Id);
                $game->theah->eventCheck($threatEvent);
                $game->theah->queueEvent($threatEvent);
    
                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }

            if ($copyReaction && $reaction instanceof Reaction_04020)
            {
                $this->copiedReactions[] = $reaction;
                $katain->IsUpdated = true;

                $reaction->beginEffectsCopy($game->theah, $this->sourceTargetLocation, $katain->Id);

                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }
        }

        $game->gamestate->nextState("done");
    }
}