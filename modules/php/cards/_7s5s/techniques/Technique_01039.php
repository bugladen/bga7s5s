<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01039 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $inDuel = $theah->game->globals->get(Game::IN_DUEL);
        if (!$inDuel)
            return false;

        $philip = $theah->getDuelRoundActor();

        $characters = $theah->getCharactersAtLocation($philip->Location);
        $characters = array_filter($characters, fn($c) => $c->ControllerId == $playerId && $c->hasTrait("Mercenary", $philip));
        if (count($characters) == 0)
            return false;

        $adversaryId = $theah->getDuelOpponentId($philip->Id);
        $adversary = $theah->getCharacterById($adversaryId);

        if ($theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        if ( ! $adversary->Engaged)
            return false;

        return true;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $philip = $this->getOwningCharacter($event->theah);

            $adversaryId = $event->theah->getDuelOpponentId($philip->Id);
            $adversary = $event->theah->getCharacterById($adversaryId);

            $mercenaries = $event->theah->getCharactersAtLocation($philip->Location);
            $mercenaries = array_filter($mercenaries, fn($character) => $character->ControllerId == $philip->ControllerId && $character->hasTrait("Mercenary", $philip));

            if ($adversary->Engaged && count($mercenaries) > 0)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $philip->Id, 1, $philip->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundEvent);
            }
        }
    }
}