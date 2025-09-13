<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

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

    public function resetCard()
    {
        parent::resetCard();
        
        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventSchemeCardRevealed) {
            if ($event->scheme->PanacheModifier != 0 && $event->playerId == $this->ControllerId) {
                $this->ModifiedPanache += $event->scheme->PanacheModifier;
                $this->IsUpdated = true;

                $event->theah->game->notifyAllPlayers("panacheModified", clienttranslate('${leader_inject_code}: Panache modified to ${panache} by ${scheme_inject_code}'), [
                    'i18n' => ['scheme_inject_code'],
                    "leader_inject_code" => $this->getInjectCode(),
                    "panache" => $this->ModifiedPanache,
                    "scheme_inject_code" => $event->scheme->getInjectCode(),
                    "playerId" => $this->ControllerId,
                    "leader" => $this->getPropertyArray($event->theah->game),
                ]);
            }
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            if ($event->theah->game->globals->get(Game::PLAYER_COUNT) == 2)
            {
                $db = $event->theah->getDBObject();
                $db->setPlayerReknown($this->ControllerId, -1);
                $transition = $event->theah->createEvent(Events::Transition);
                if ($transition instanceof EventTransition)
                {
                    $transition->playerId = $this->ControllerId;
                    $transition->transition = "endOfGame";
                }
                $event->theah->queueEvent($transition);
            }
            else
            {
                $db = $event->theah->getDBObject();
                $current = $db->getPlayerReknown($this->ControllerId);

                //Modify current by half, rounded up
                $new = ceil($current / 2);

                $event->theah->game->notifyAllPlayers("message", clienttranslate('${player_name} will lose half of their reknown (${old_reknown} to ${new_reknown}).'), [
                    "player_name" => $event->theah->game->getPlayerNameById($this->ControllerId),
                    "old_reknown" => $current,
                    "new_reknown" => $new,
                ]);

                $reknown = $event->theah->createEvent(Events::PlayerLosesReknown);
                if ($reknown instanceof EventPlayerLosesReknown) {
                    $reknown->playerId = $this->ControllerId;
                    $reknown->amount = $current - $new;
                }
                $event->theah->queueEvent($reknown);
            }
        }
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);

        //Add leader specific properties
        $properties['crewCap'] = $this->CrewCap;
        $properties['modifiedCrewCap'] = $this->ModifiedCrewCap;
        $properties['panache'] = $this->Panache;
        $properties['modifiedPanache'] = $this->ModifiedPanache;

        return $properties;
    }

}