<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class CardAbility
{
    public string $Id;
    protected string $ClassId;
    public int $OwnerId;
    public string $Name;
    public bool $Active;
    public bool $Used;

    public function __construct()
    {
        $classname = get_class($this);
        $pos = strrpos($classname, '\\');        

        //Use unqualified class name as Id and ClassId
        //Id will updated when setOwnerId is called to add uniqueness to the Id
        $this->Id = substr($classname, $pos + 1);
        $this->ClassId = $this->Id;

        $this->Name = "";
        $this->Active = false;
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

    public function eventCheck(Event $event)
    {
        
    }

    public function handleEvent(Event $event)
    {
        //Does nothing by default
    }

    public function isActive(): bool
    {
        return $this->Active;
    }

    public function isAvailable(): bool
    {
        return $this->Used == false;
    }
    
    public function getOwningCharacter(Theah $theah): ?Character
    {
        if ($this->OwnerId == null) {
            return null;
        }

        $owner = $theah->getCardById($this->OwnerId);
        if ($owner instanceof Character) {
            return $owner;
        }

        if ($owner instanceof Attachment) {
            $id = $owner->AttachedToId;
            $owner = $theah->getCardById($id);
            return $owner;
        }

        return null;
    }

    public function setActive(Theah $theah, bool $active)
    {
        $this->Active = $active;

        if ($this->OwnerId == null) {
            return;
        }

        $owner = $theah->getCardById($this->OwnerId);
        $owner->IsUpdated = true;
    }

    public function setUsed(Theah $theah, bool $used)
    {
        $this->Used = $used;

        if ($this->OwnerId == null) {
            return;
        }

        $owner = $theah->getCardById($this->OwnerId);
        $owner->IsUpdated = true;
    }

    public function cleanup()
    {
        $this->Active = false;
        $this->Used = false;
    }
}