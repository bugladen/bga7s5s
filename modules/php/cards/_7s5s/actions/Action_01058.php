<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01058 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Opposing Character Home Engaged");
        $this->RequiresPerformerSelected = true;
    }

    private function getAvailablePerformers(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $availablePerformers = [];
        foreach ($performers as $performer)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($performer->Location);
            $opposingCharacters = array_values(array_filter($opposingCharacters, fn($opposingCharacter) => 
                            $opposingCharacter->isNotControlledByPlayer($playerId) && 
                            $opposingCharacter->ModifiedCombat < $performer->ModifiedCombat &&
                            ! $opposingCharacter instanceof Leader));     

            if (count($opposingCharacters) > 0)
                $availablePerformers[] = $performer;
        }

        return $availablePerformers;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $performers = $this->getAvailablePerformers($playerId, $theah);
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getAvailablePerformers($playerId, $theah);        
    }
}