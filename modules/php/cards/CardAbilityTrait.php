<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Reaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

trait CardAbilityTrait
{
    public string $Id;
    public string $ClassId;
    public int $OwnerId;
    public string $Name;
    public bool $Used;

    public function initializeAbility()
    {
        $classname = get_class($this);
        $pos = strrpos($classname, '\\');        

        //Use unqualified class name as Id and ClassId
        //Id will updated when setOwnerId is called to add uniqueness to the Id
        $this->Id = substr($classname, $pos + 1);
        $this->ClassId = $this->Id;

        $this->Name = "";
        $this->Used = false;
    }

    public function setId($id)
    {
        $this->Id = $id;
        $this->ClassId = $id;
    }

    public function setOwnerId($id)
    {
        $this->OwnerId = $id;
        $this->Id = "{$id}_{$this->ClassId}";
    }

    public function isAvailable(): bool
    {
        return $this->Used == false;
    }
    
    public function getOwningCard(Theah $theah): ?Card
    {
        if ($this->OwnerId == null) {
            return null;
        }

        $owner = $theah->getCardById($this->OwnerId);
        if ( ! $owner)
            $owner = $theah->game->getCardObjectFromDb($this->OwnerId);

        return $owner;
    }

    public function getOwningAttachment(Theah $theah): ?Attachment
    {
        if ($this->OwnerId == null) {
            return null;
        }

        $owner = $theah->getCardById($this->OwnerId);
        if ( ! $owner)
            $owner = $theah->game->getCardObjectFromDb($this->OwnerId);

        if ($owner instanceof Attachment)
            return $owner;
        else
            return null;
    }

    public function getOwningCharacter(Theah $theah): ?Character
    {
        if ($this->OwnerId == null) {
            return null;
        }

        $owner = $theah->getCardById($this->OwnerId);

        if ($owner instanceof Character)
            return $owner;
        else if ($owner instanceof Attachment and $owner->isAttached()) {
            $id = $owner->AttachedToId;
            $owner = $theah->getCardById($id);
            return $owner;
        }
        else
            return null;
    }

    public function getPropertyArray(Game $game): array
    {
        $owner = $this->getOwningCard($game->theah);
        $name = $game->translate($this->Name);
        return [
            "id" => $this->Id, 
            "name" => $game->translate($owner->Name) . ': ' . $name,
            "shortName" => $name,
            "available" => $this->isAvailable()
        ];
    }

    public function setUsed(Theah $theah, bool $used)
    {
        $this->Used = $used;

        if ($this->OwnerId == null) {
            return;
        }

        $owner = $theah->getCardById($this->OwnerId);
        $theah->game->updateCardObjectInDb($owner);

        if ($this instanceof Action)
            $event = EventFactory::createActionUsedEvent($owner->ControllerId, $owner->Id, $this->Id, $used);
        else if ($this instanceof Reaction)
            $event = EventFactory::createReactionUsedEvent($owner->ControllerId, $owner->Id, $this->Id, $used);
        else if ($this instanceof Maneuver)
            $event = EventFactory::createManeuverUsedEvent($owner->ControllerId, $owner->Id, $this->Id, $used);
        else if ($this instanceof Technique)
            $event = EventFactory::createTechniqueUsedEvent($owner->ControllerId, $owner->Id, $this->Id, $used);

        $theah->queueEvent($event);
    }
}