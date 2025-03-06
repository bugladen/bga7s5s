<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;

interface IHasActions
{
    public function getActions(): Array;

    public function addActionProperties(&$properties);

    public function anyActionsAvailable(): bool;

    public function getActionById($id): ?Action;

    public function getActionNames($includeAvailable = false): Array;

    public function getActionsArray(): Array;

    public function updateActionOwnerIds($id);    
}