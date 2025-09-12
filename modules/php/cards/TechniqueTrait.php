<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;

trait TechniqueTrait
{
    protected Array $Techniques = [];

    public function getTechniques(): Array
    {
        return $this->Techniques;
    }

    public function addTechniqueProperties(Game $game, &$properties)
    {
        //Add technique specific properties
        $properties['numberofTechniques'] = count($this->Techniques);
        $properties['techniques'] = $this->getTechniquesArray($game, $mustBeAvailable = false);
    }

    public function getTechniquesAvailableToPlayer(Game $game, int $playerId): Array
    {
        $array = [];
        foreach ($this->Techniques as $technique) {
            if ($technique->isAvailableToPlayer($playerId, $game->theah))
                $array[] = $technique;
        }
        return $array;
    }

    public function getTechniquesArray(Game $game, bool $mustBeAvailable = false): Array
    {
        $array = [];
        foreach ($this->Techniques as $technique) 
        {
            if ($mustBeAvailable && (!$technique->isAvailable() || !$technique->isAvailableToPlayer($game->getActivePlayerId(), $game->theah))) 
                continue;

            $array[] = $technique->getPropertyArray($game);
        }

        return $array;
    }

    public function getTechniqueById($id): ?Technique
    {
        foreach ($this->Techniques as $technique) {
            if ($technique->Id == $id)
                return $technique;
        }
        return null;
    }

    public function getTechniqueByClassId($classId): ?Technique
    {
        foreach ($this->Techniques as $technique) {
            if ($technique->ClassId == $classId)
                return $technique;
        }
        return null;
    }

    public function updateTechniqueOwnerIds($id)
    {
        foreach ($this->Techniques as $technique) {
            $technique->setOwnerId($id);
        }
    }

    public function addTechnique(Technique $technique, Game $game)
    {
        //Check if the technique is already in the list.
        if (in_array($technique, $this->Techniques))
        {
            return;
        }

        $this->Techniques[] = $technique;

        $game->notifyAllPlayers('techniqueAdded', clienttranslate('${character_inject_code} has gained Technique: ${technique_name}.'), [
            'i18n' => ['technique_name'],
            'character_inject_code' => $this->getInjectCode(),
            'characterId' => $this->Id,
            'technique' => $technique->getPropertyArray($game),
            'technique_name' => $technique->Name
        ]);

    }

    public function removeTechnique(Technique $technique, Game $game)
    {
        $this->Techniques = array_filter($this->Techniques, fn($t) => $t->Id != $technique->Id);

        $game->notifyAllPlayers('techniqueRemoved', clienttranslate('${character_inject_code} has lost Technique: ${technique_name}.'), [
            'i18n' => ['technique_name'],
            'character_inject_code' => $this->getInjectCode(),
            'characterId' => $this->Id,
            'techniqueId' => $technique->Id,
            'technique_name' => $technique->Name
        ]);
    }
}