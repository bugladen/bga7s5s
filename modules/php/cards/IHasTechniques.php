<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

interface IHasTechniques
{
    public function getTechniques(): Array;

    public function addTechniqueProperties(Game $game, &$properties);

    public function anyTechniquesAvailable(): bool;

    public function getTechniqueById($id): ?Technique;
    
    public function getTechniqueByClassId($classId): ?Technique;

    public function getTechniquesArray(Game $game, bool $mustBeAvailable = false): Array;

    public function updateTechniqueOwnerIds($id);

    public function addTechnique(Technique $technique);

    public function removeTechnique(Technique $technique);
}