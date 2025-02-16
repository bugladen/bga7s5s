<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;

interface IHasTechniques
{
    public function getTechniques(): Array;

    public function addTechniqueProperties(&$properties);

    public function anyTechniquesAvailable(): bool;

    public function getTechniqueById($id): ?Technique;

    public function getTechniqueNames($includeAvailable = false): Array;

    public function getTechniquesArray(): Array;

    public function updateTechniqueOwnerIds($id);
}