<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03004 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Parry and Wound the Adversary (combat card is Sorcery)");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        // "If Elena's combat card is a Sorcery"
        $combatCards = $theah->getCombatCardsForCurrentRound();
        foreach ($combatCards as $combatCard)
        {
            if ($combatCard->ControllerId == $owner->ControllerId && $combatCard->hasTrait("Sorcery"))
            {
                return true;
            }
        }
        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);

            $event->parry += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds +1 Parry."), $owner->getInjectCode(), $this->Name);

            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary !== null)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundEvent);
            }
        }
    }
}
