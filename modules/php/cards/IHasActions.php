<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

interface IHasActions
{
    public function getActions(): Array;

    public function addActionProperties(Game $game, &$properties);

    public function anyActionsAvailable(): bool;

    public function getActionById($id): ?Action;

    public function getActionNames($includeAvailable = false): Array;

    public function getActionsArray(Game $game): Array;

    public function updateActionOwnerIds($id);

    public function addAction(CardAction $action, Game $game);

    public function removeAction(CardAction $action, Game $game);
}