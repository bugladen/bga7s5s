<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

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
        $this->Faction = "";
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
            $actionId = $game->globals->get(Game::CHOSEN_ACTION);
            $action = $this->getActionById($actionId);
            if ($action)
            {
                $args += $action->getArgsFromAction($game, $state, $stateName);
            }
        }

        return $args; 
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $actionId): void 
    { 
        if ($this instanceof IHasActions)
        {
            $action = $this->getActionById($actionId);
            if ($action)
            {
                $action->actFromActionPass($game, $state, $stateName);
            }
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $actionId, int $id): void 
    { 
        if ($this instanceof IHasActions)
        {
            $action = $this->getActionById($actionId);
            if ($action)
            {
                $action->actFromActionWithId($game, $state, $stateName, $id);
            }
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $actionId, array $ids): void 
    { 

        if ($this instanceof IHasActions)
        {
            $actionId = $game->globals->get(Game::CHOSEN_ACTION);
            $action = $this->getActionById($actionId);
            if ($action)
            {
                $action->actFromActionWithIds($game, $state, $stateName, $ids);
            }
        }
    }

    public function eventCheck($event)
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
        $this->Conditions = array_filter($this->Conditions, fn($c) => $c != $condition );
        $this->IsUpdated = true;
    }

    public function getParryModification(Theah $theah): int
    {
        return 0;
    }

    public function getPressureTypesForClaim(Theah $theah, Character $performer, Array &$pressureTypes): void {}
    
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
        $properties['traits'] = $this->Traits;
        $properties['conditions'] = $this->Conditions;

        if ($this instanceof IWealthCost) $this->addWealthCostProperties($properties);
        if ($this instanceof ICityDeckCard) $this->addCityProperties($properties);
        if ($this instanceof IFactionCard) $this->addFactionProperties($properties);
        if ($this instanceof IHasTechniques) $this->addTechniqueProperties($game, $properties);
        if ($this instanceof IHasManeuvers) $this->addManeuverProperties($game, $properties);
        if ($this instanceof IHasActions) $this->addActionProperties($game, $properties);
        if ($this instanceof IHasReactions) $this->addReactionProperties($game, $properties);

        return $properties;
    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $reaction): int
    {
        return 0;
    }
}