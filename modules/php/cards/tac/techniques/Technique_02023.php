<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02023 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("If the adversary is a Thug or Mercenary • +1 Thrust");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL);
        if (! $inDuel)
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary->hasTrait("Thug") || $adversary->hasTrait("Mercenary"))
        {
            return true;
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 1 Thrust because the adversary is a Thug or Mercenary."), $owner->getInjectCode(), $this->Name);
        }
    }
}