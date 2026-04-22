<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_02041 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Duel of Finesse');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor->hasTrait("Scoundrel");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);

            $event->theah->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);

            $event->theah->game->notify->all("message", clienttranslate('${card_inject_code}: This duel becomes a duel of Finesse.'), [
                "card_inject_code" => $owner->getInjectCode(),
            ]);
        }
    }
}
