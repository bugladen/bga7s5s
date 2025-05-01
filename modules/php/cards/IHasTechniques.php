<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

interface IHasTechniques
{
    public function getTechniques(): Array;

    public function addTechniqueProperties(&$properties);

    public function anyTechniquesAvailable(): bool;

    public function getTechniqueById($id): ?Technique;

    public function getTechniqueNames(Game $game, $includeAvailable = false): Array;

    public function getTechniquesArray(): Array;

    public function updateTechniqueOwnerIds($id);
}