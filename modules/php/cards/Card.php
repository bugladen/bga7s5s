<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Card
{
    public int $Id; 
    public int $OwnerId;
    public int $ControllerId;
    public string $Name;
    public string $Image;
    public string $ExpansionName;
    public int $ExpansionNumber;
    public int $CardNumber;
    public string $Faction;
    public bool $Engaged;
    public Array $Traits = [];
    public Array $ModifiedTraits = [];
    public Array $Conditions = [];

    public string $Location;
    public bool $IsUpdated;
    public int $Reknown;

    public function __construct()
    {
        $this->Id = 0;
        $this->OwnerId = 0;
        $this->ControllerId = 0;
        $this->Name = "";
        $this->Image = "";
        $this->ExpansionName = "";
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Faction = "Neutral";
        $this->Engaged = false;

        $this->Location = "";
        $this->IsUpdated = false;
        $this->Reknown = 0;
    }

    public function setId($id)
    {
        $this->Id = $id;
        if ($this instanceof IHasTechniques) {
            $this->updateTechniqueOwnerIds($id);
        }
        if ($this instanceof IHasManeuvers) {
            $this->updateManeuverOwnerIds($id);
        }
        if ($this instanceof IHasActions) {
            $this->updateActionOwnerIds($id);
        }
        if ($this instanceof IHasReactions) {
            $this->updateReactionOwnerIds($id);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array 
    {
        $args = [];

        if ($stateName == "playerReaction" && $this instanceof IHasReactions) 
            $this->updateArgsFromReaction($game, $args, $state, $stateName, $internalId);

        if ($stateName == "playerPayForReaction" && $this instanceof IHasReactions) 
            $this->updatePayForArgsFromReaction($game, $args, $state, $stateName, $internalId);

        if ($this instanceof IHasActions) 
        {
            $action = $this->getActionById($internalId);
            if ($action)
            {
                $args += $action->getArgsFromAction($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasTechniques)
        {
            $technique = $this->getTechniqueById($internalId);
            if ($technique)
            {
                $args += $technique->getArgsFromTechnique($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasManeuvers)
        {
            $maneuver = $this->getManeuverById($internalId);
            if ($maneuver)
            {
                $args += $maneuver->getArgsFromManeuver($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasReactions)
        {
            $reaction = $this->getReactionById($internalId);
            if ($reaction)
            {
                $args += $reaction->getArgsFromReaction($game, $state, $stateName);
            }
        }

        return $args; 
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void 
    { 
        if ($this instanceof IHasActions)
        {
            $action = $this->getActionById($internalId);
            if ($action)
            {
                $action->actFromActionPass($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasTechniques)
        {
            $technique = $this->getTechniqueById($internalId);
            if ($technique)
            {
                $technique->actFromTechniquePass($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasManeuvers)
        {
            $maneuver = $this->getManeuverById($internalId);
            if ($maneuver)
            {
                $maneuver->actFromManeuverPass($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasReactions)
        {
            $reaction = $this->getReactionById($internalId);
            if ($reaction)
            {
                $reaction->actFromReactionPass($game, $state, $stateName);
            }
        }

        $game->notify->all("message", clienttranslate('${player_name} passes.'), [
            "player_name" => $game->getPlayerNameById($this->ControllerId),
        ]);
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void 
    { 
        if ($this instanceof IHasActions)
        {
            $action = $this->getActionById($internalId);
            if ($action)
            {
                $action->actFromActionWithId($game, $state, $stateName, $id);
            }
        }

        if ($this instanceof IHasTechniques)
        {
            $technique = $this->getTechniqueById($internalId);
            if ($technique)
            {
                $technique->actFromTechniqueWithId($game, $state, $stateName, $id);
            }
        }

        if ($this instanceof IHasManeuvers)
        {
            $maneuver = $this->getManeuverById($internalId);
            if ($maneuver)
            {
                $maneuver->actFromManeuverWithId($game, $state, $stateName, $id);
            }
        }

        if ($this instanceof IHasReactions)
        {
            $reaction = $this->getReactionById($internalId);
            if ($reaction)
            {
                $reaction->actFromReactionWithId($game, $state, $stateName, $id);
            }
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void 
    { 
        if ($this instanceof IHasActions)
        {
            $action = $this->getActionById($internalId);
            if ($action)
            {
                $action->actFromActionWithIds($game, $state, $stateName, $ids);
            }
        }

        if ($this instanceof IHasTechniques)
        {
            $technique = $this->getTechniqueById($internalId);
            if ($technique)
            {
                $technique->actFromTechniqueWithIds($game, $state, $stateName, $ids);
            }
        }

        if ($this instanceof IHasManeuvers)
        {
            $maneuver = $this->getManeuverById($internalId);
            if ($maneuver)
            {
                $maneuver->actFromManeuverWithIds($game, $state, $stateName, $ids);
            }
        }

        if ($this instanceof IHasReactions)
        {
            $reaction = $this->getReactionById($internalId);
            if ($reaction)
            {
                $reaction->actFromReactionWithIds($game, $state, $stateName, $ids);
            }
        }
    }

    public function actFromCardWithActionId(Game $game, int $state, string $stateName, string $internalId, int $actionSourceId, string $actionId): void
    {
        if ($this instanceof IHasActions)
        {
            $action = $this->getActionById($internalId);
            if ($action)
            {
                $action->actFromActionWithActionId($game, $state, $stateName, $actionSourceId, $actionId);
            }
        }
    }

    public function getAbilityById($id): ?ICardAbility
    {
        if ($this instanceof IHasActions)
        {
            return $this->getActionById($id);
        }
        if ($this instanceof IHasTechniques)
        {
            return $this->getTechniqueById($id);
        }
        if ($this instanceof IHasManeuvers)
        {
            return $this->getManeuverById($id);
        }
        if ($this instanceof IHasReactions)
        {
            return $this->getReactionById($id);
        }

        return null;
    }

    public function stateFromCard(Game $game, int $state, string $stateName, string $internalId): void
    {
        if ($this instanceof IHasActions)
        {
            $action = $this->getActionById($internalId);
            if ($action)
            {
                $action->stateFromAction($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasTechniques)
        {
            $technique = $this->getTechniqueById($internalId);
            if ($technique)
            {
                $technique->stateFromTechnique($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasManeuvers)
        {
            $maneuver = $this->getManeuverById($internalId);
            if ($maneuver)
            {
                $maneuver->stateFromManeuver($game, $state, $stateName);
            }
        }

        if ($this instanceof IHasReactions)
        {
            $reaction = $this->getReactionById($internalId);
            if ($reaction)
            {
                $reaction->stateFromReaction($game, $state, $stateName);
            }
        }
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment, Array &$explanations): int 
    { 
        $discount = 0;
        if ($this instanceof IHasReactions)
        {
            foreach ($this->getReactions() as $reaction)
            {
                $discount += $reaction->getEquipDiscount($theah, $performer, $attachment, $explanations);
            }
        }

        return $discount;
    }

    public function getParleyDiscount(Theah $theah, Character $performer, bool $parleying, Array &$explanations) : int { return 0; }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, Array &$explanations): int 
    {
        $count = 0;
        if ($this instanceof IHasReactions)
        {
            foreach ($this->getReactions() as $reaction)
            {
                $count += $reaction->getNumberOfGambleCardsToReveal($theah, $actor, $explanations);
            }
        }

        if ($this instanceof IHasManeuvers)
        {
            foreach ($this->getManeuvers() as $maneuver)
            {
                $count += $maneuver->getNumberOfGambleCardsToReveal($theah, $actor, $explanations);
            }
        }

        if ($this instanceof IHasTechniques)
        {
            foreach ($this->getTechniques() as $technique)
            {
                $count += $technique->getNumberOfGambleCardsToReveal($theah, $actor, $explanations);
            }
        }

        return $count;
    }

    public function getPlayBruteDiscount(Theah $theah, Character $brute): int { return 0; }

    public function eventCheck(Event $event)
    {
        if ($this instanceof IHasTechniques) {
            foreach ($this->getTechniques() as $technique) {
                $technique->eventCheck($event);
            }
        }
        if ($this instanceof IHasManeuvers) {
            foreach ($this->getManeuvers() as $maneuver) {
                $maneuver->eventCheck($event);
            }
        }
        if ($this instanceof IHasActions) {
            foreach ($this->getActions() as $action) {
                $action->eventCheck($event);
            }
        }
        if ($this instanceof IHasReactions) {
            foreach ($this->getReactions() as $reaction) {
                $reaction->eventCheck($event);
            }
        }
    }
    
    public function handleEvent(Event $event)
    {
        if ($this instanceof IHasTechniques) {
            foreach ($this->getTechniques() as $technique) {
                $technique->handleEvent($event);
            }
        }
        
        if ($this instanceof IHasManeuvers) {
            foreach ($this->getManeuvers() as $maneuver) {
                $maneuver->handleEvent($event);
            }
        }

        if ($this instanceof IHasActions) {
            foreach ($this->getActions() as $action) {
                $action->handleEvent($event);
            }
        }
        if ($this instanceof IHasReactions) {
            foreach ($this->getReactions() as $reaction) {
                $reaction->handleEvent($event);
            }
        }
    }

    public function addCondition($condition)
    {
        $this->Conditions[] = $condition;
        $this->IsUpdated = true;
    }

    public function hasCondition($condition)
    {
        return in_array($condition, $this->Conditions);
    }

    public function removeCondition($condition)
    {
        $this->Conditions = array_values(array_filter($this->Conditions, fn($c) => $c != $condition ));
        $this->IsUpdated = true;
    }

    public function getParryModification(Theah $theah): int
    {
        return 0;
    }

    public function getPressureStats(Theah $theah, Character $performer, Array &$pressureTypes): void {}
    
    public function getPropertyArray(Game $game)
    {
        $properties = [
            'id' => $this->Id,
            'ownerId' => $this->OwnerId,
            'controllerId' => $this->ControllerId,
            'name' => $this->Name,
            'image' => $this->Image,
            'faction' => $this->Faction,
            'location' => $this->Location,
            'engaged' => $this->Engaged,
            'reknown' => $this->Reknown,
        ];

        $properties['type'] = 'Card';

        //Hack to prevent older games from breaking
        if (empty($this->ModifiedTraits))
            $this->ModifiedTraits = $this->Traits;

        $properties['traits'] = array_values($this->ModifiedTraits);
        $properties['conditions'] = array_values($this->Conditions);

        if ($this instanceof IWealthCost) $this->addWealthCostProperties($properties);
        if ($this instanceof ICityDeckCard) $this->addCityProperties($properties);
        if ($this instanceof IFactionCard) $this->addFactionProperties($properties);
        if ($this instanceof IHasTechniques) $this->addTechniqueProperties($game, $properties);
        if ($this instanceof IHasManeuvers) $this->addManeuverProperties($game, $properties);
        if ($this instanceof IHasActions) $this->addActionProperties($game, $properties);
        if ($this instanceof IHasReactions) $this->addReactionProperties($game, $properties);

        return $properties;
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $requestedAction, Array &$explanations): int
    {
        $discount = 0;
        if ($this instanceof IHasActions)
        {
            foreach ($this->getActions() as $action)
            {
                $discount += $action->getActionFromHandDiscount($theah, $performer, $requestedAction, $explanations);
            }
        }

        if ($this instanceof IHasReactions)
        {
            foreach ($this->getReactions() as $reaction)
            {
                $discount += $reaction->getActionFromHandDiscount($theah, $performer, $requestedAction, $explanations);
            }
        }

        return $discount;
    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $requestedReaction, Array &$explanations): int
    {
        $discount = 0;
        if ($this instanceof IHasReactions)
        {
            foreach ($this->getReactions() as $reaction)
            {
                $discount += $reaction->getReactionFromHandDiscount($theah, $requestedReaction, $explanations);
            }
        }

        return $discount;
    }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Risk $combatCard, Array &$explanations): int
    {
        $discount = 0;
        if ($this instanceof IHasReactions)
        {
            foreach ($this->getReactions() as $reaction)
            {
                $discount += $reaction->getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);
            }
        }
        return $discount;
    }

    public function hasTrait(string $trait, ?Card $queryCard = null): bool
    {
        //Hack to prevent older games from breaking
        if (empty($this->ModifiedTraits))
            $this->ModifiedTraits = $this->Traits;

        return in_array($trait, $this->ModifiedTraits);
    }

    public function isControlled(): bool
    {
        return $this->ControllerId != 0;
    }

    public function isNotControlledByPlayer(int $playerId): bool
    {
        return $this->isControlled() && $this->ControllerId != $playerId;
    }

    public function addTrait(Game $game, string $trait): void
    {
        //Hack to prevent older games from breaking
        if (empty($this->ModifiedTraits))
            $this->ModifiedTraits = $this->Traits;

        $this->ModifiedTraits[] = $trait;
        $this->IsUpdated = true;

        $game->notify->all("traitAdded", clienttranslate('${character_inject_code} gains [${trait}].'), [
            "character_inject_code" => $this->getInjectCode(),
            "characterId" => $this->Id,
            'trait' => $trait,
        ]);
    }

    public function removeTrait(Game $game, string $trait): void
    {
        //Hack to prevent older games from breaking
        if (empty($this->ModifiedTraits))
            $this->ModifiedTraits = $this->Traits;

        $index = array_search($trait, $this->ModifiedTraits);
        if ($index !== false)
        {
            unset($this->ModifiedTraits[$index]);
        }
        $this->ModifiedTraits = array_values($this->ModifiedTraits);
        $this->IsUpdated = true;

        $game->notify->all("traitRemoved", clienttranslate('${character_inject_code} loses [${trait}].'), [
            "character_inject_code" => $this->getInjectCode(),
            "characterId" => $this->Id,
            'trait' => $trait,
        ]);
    }

    public function hasManeuversAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ($this instanceof IHasManeuvers)
        {
            foreach ($this->getManeuvers() as $maneuver)
            {
                if ($maneuver->isAvailableToPlayer($playerId, $theah))
                {
                    return true;
                }
            }
        }

        return false;
    }

    //This will return a string that can be used to inject the card tooltip into the game log on the client
    public function getInjectCode(): string
    {
        return sprintf('[%s(%s)]', $this->Name, $this->Image);
    }

    public function resetCard()
    {
        $this->ModifiedTraits = $this->Traits;
    }
}