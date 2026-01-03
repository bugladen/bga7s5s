<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01066 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+2 Thrust if Adversary is only Enemy at Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
            return false;

        $adversary = $theah->getDuelRoundOpponent();
        $charactersAtLocation = $theah->getCharactersAtLocationByPlayerId($adversary->Location, $adversary->ControllerId);
        return count($charactersAtLocation) == 1;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $event->thrust += 2;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 2 Thrust if Adversary is only Enemy at Location."), $owner->getInjectCode(), $this->Name);
        }
    }
}