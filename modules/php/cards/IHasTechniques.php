<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;

interface IHasTechniques
{
    public function addTechniqueProperties(&$properties);

    public function anyTechniquesAvailable(): bool;

    public function getTechniqueNames(): Array;

    public function getTechniquesArray(): Array;

    public function getTechniqueById($id): ?Technique;

    public function updateTechniqeOwnerIds($id);
}