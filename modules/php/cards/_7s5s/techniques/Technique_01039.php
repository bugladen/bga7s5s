<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_01039 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound Adversary");
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $philip = $this->getOwningCharacter($event->theah);

            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL);
            $adversaryId = 0;
            if ($inDuel)
            {
                $adversaryId = $event->theah->getDuelOpponentId($philip->Id);
            }
            else
            {
                $adversaryId = $game->globals->get(Game::CHOSEN_TARGET);
            }

            $adversary = $event->theah->getCharacterById($adversaryId);

            $mercenaries = $event->theah->getCharactersAtLocation($philip->Location);
            $mercenaries = array_filter($mercenaries, fn($character) => $character->ControllerId == $philip->ControllerId && $character->hasTrait("Mercenary"));

            if ($adversary->Engaged && count($mercenaries) > 0)
            {
                $woundEvent = EventFactory::createCharacterWoundedEvent($adversary->Id, $philip->Id, 1, $game->translate("Wound Adversary"), $this->Id);
                $event->theah->queueEvent($woundEvent);
            }
        }
    }
}