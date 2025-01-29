<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Attribute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

abstract class Technique
{
    public string $Id;
    private string $ClassId;
    public int $OwnerId;
    public string $Name;
    public bool $Active;
    public bool $Used;
    public bool $ResetOnDuelEnd;
    public bool $ResetOnDayEnd;

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
        $this->ResetOnDuelEnd = true;
        $this->ResetOnDayEnd = false;
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

    public function handleEvent(Card $owner, Event $event)
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
        if ($this->OwnerId == null) {
            return;
        }

        $this->Active = $active;

        $owner = $theah->getCardById($this->OwnerId);
        $owner->IsUpdated = true;
    }

    public function setUsed(Theah $theah, bool $used)
    {
        if ($this->OwnerId == null) {
            return;
        }

        $this->Used = $used;

        $owner = $theah->getCardById($this->OwnerId);
        $owner->IsUpdated = true;
    }
}