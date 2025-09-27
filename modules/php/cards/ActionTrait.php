<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

trait ActionTrait
{
    protected Array $Actions = [];

    public function getActions(): Array
    {
        return $this->Actions;
    }

    public function addActionProperties(Game $game, &$properties)
    {
        $properties['numberofActions'] = count($this->Actions);
        $properties['actions'] = $this->getActionsArray($game);
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

    public function getActionsArray(Game $game, bool $mustBeAvailable = false): Array
    {
        $array = [];
        foreach ($this->Actions as $action) {
            if ($mustBeAvailable && !$action->isAvailable()) {
                continue;   
            }
            $array[] = $action->getPropertyArray($game);
        }

        return $array;
    }

    public function updateActionOwnerIds($id)
    {
        foreach ($this->Actions as $action)
            $action->setOwnerId($id);
    }

    public function addAction(CardAction $action, Game $game)
    {
        $this->Actions[] = $action;

        $game->notifyAllPlayers('actionAdded', clienttranslate('${character_inject_code} has gained Action: ${action_name}.'), [
            'i18n' => ['action_name'],
            'character_inject_code' => $this->getInjectCode(),
            'characterId' => $this->Id,
            'action' => $action->getPropertyArray($game),
            'action_name' => $action->Name
        ]);
    }

    public function removeAction(CardAction $action, Game $game)
    {
        $this->Actions = array_filter($this->Actions, fn($a) => $a->Id != $action->Id);
        $game->notifyAllPlayers('actionRemoved', clienttranslate('${character_inject_code} has lost Action: ${action_name}.'), [
            'i18n' => ['action_name'],
            'character_inject_code' => $this->getInjectCode(),
            'characterId' => $this->Id,
            'actionId' => $action->Id,
            'action_name' => $action->Name
        ]);
    }
}