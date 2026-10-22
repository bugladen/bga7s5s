<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_GainLethal;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// EventTechniqueCanceled handler not needed — Gain Lethal has no sticky state.
class Technique_05Sniper extends Technique_GainLethal
{
    public function __construct()
    {
        parent::__construct();

        // WHY: test card — plain string. The generic constructor wraps the same
        // words; overwrite so this card does not keep that wrapper.
        $this->Name = "Gain Lethal";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // WHY: In a duel the actor must be Eleanor. Outside a duel (challenge
        // threat) the technique is listed from the performer, so no actor gate.
        if ($theah->game->globals->get(Game::IN_DUEL, false))
        {
            $owner = $this->getOwningCharacter($theah);
            $actor = $theah->getDuelRoundActor();
            if ($actor === null || $owner === null || $actor->Id !== $owner->Id)
            {
                return false;
            }
        }

        return true;
    }
}
