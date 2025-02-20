<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;

abstract class Leader extends Character
{

    public int $CrewCap;
    public int $ModifiedCrewCap;
    public int $Panache;
    public int $ModifiedPanache;

    public function __construct(){
        parent::__construct();

        $this->CrewCap = 0;
        $this->ModifiedCrewCap = 0;
        $this->Panache = 0;
        $this->ModifiedPanache = 0;
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventSchemeCardRevealed) {
            if ($event->scheme->PanacheModifier != 0 && $event->playerId == $this->ControllerId) {
                $this->ModifiedPanache += $event->scheme->PanacheModifier;
                $this->IsUpdated = true;

                $event->theah->game->notifyAllPlayers("panacheModified", clienttranslate('${leader_name}: Panache modified to ${panache} by ${scheme_name}'), [
                    "leader_name" => "<span style='font-weight:bold'>$this->Name</span>",
                    "panache" => $this->ModifiedPanache,
                    "scheme_name" => $event->scheme->Name,
                    "playerId" => $this->ControllerId,
                    "leader" => $this->getPropertyArray(),
                ]);
            }
        }
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        //Add leader specific properties
        $properties['crewCap'] = $this->CrewCap;
        $properties['modifiedCrewCap'] = $this->ModifiedCrewCap;
        $properties['panache'] = $this->Panache;
        $properties['modifiedPanache'] = $this->ModifiedPanache;

        return $properties;
    }

}