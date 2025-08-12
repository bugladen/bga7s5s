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

        $actor = $theah->getDuelRoundActor();

        $characters = $theah->getCharactersAtLocation($actor->Location);
        $characters = array_filter($characters, fn($c) => $c->ControllerId == $playerId && $c->hasTrait("Mercenary"));
        if (count($characters) == 0)
            return false;

        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        $adversary = $theah->getCharacterById($adversaryId);

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
                $woundEvent = EventFactory::createCharacterWoundedEvent($adversary->Id, $philip->Id, 1, $philip->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundEvent);
            }
        }
    }
}