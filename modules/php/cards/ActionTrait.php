<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;

trait ActionTrait
{
    protected Array $Actions = [];

    public function getActions(): Array
    {
        return $this->Actions;
    }

    public function addActionProperties(&$properties)
    {
        $properties['actions'] = $this->getActionsArray();
    }

    public function anyActionsAvailable(): bool
    {
        foreach ($this->Actions as $action)
        {
            if ($action->IsAvailable())
            {
                return true;
            }
        }
        return false;
    }

    public function getActionById($id): ?Action
    {
        foreach ($this->Actions as $action)
        {
            if ($action->Id == $id)
            {
                return $action;
            }
        }
        return null;
    }

    public function getActionNames($includeAvailable = false): Array
    {
        $names = [];
        foreach ($this->Actions as $action)
        {
            if ($includeAvailable || $action->IsAvailable())
            {
                $names[] = $action->Name;
            }
        }
        return $names;
    }

    public function getActionsArray(bool $mustBeAvailable = false): Array
    {
        $array = [];
        foreach ($this->Actions as $action) {
            if ($mustBeAvailable && !$action->isAvailable()) {
                continue;
            }
            $array[] = ["id" => $action->Id, "name" => $action->Name];
        }

        return $array;
    }

    public function updateActionOwnerIds($id)
    {
        foreach ($this->Actions as $action)
            $action->setOwnerId($id);
    }
}