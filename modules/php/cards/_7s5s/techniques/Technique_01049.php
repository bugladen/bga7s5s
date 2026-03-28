<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01049 extends Technique implements IRangedAbility
{
    public ?int $originalAttachmentId = null;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Gain Lethal");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }
        
        $owner = $this->getOwningCard($theah);
        return ! $owner->Engaged;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $engageId = $this->originalAttachmentId ?? $owner->Id;
            $engageEvent = EventFactory::createCardEngagedEvent($event->playerId, $engageId, $owner->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $lethalEvent = EventFactory::createGainLethalEvent($event->actorId, $event->theah);
            $event->theah->queueEvent($lethalEvent);

            $owner = $this->getOwningCard($event->theah);
            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $event->actorId);
            $event->theah->queueEvent($rangedAbilityPlayedEvent);
        }
    } 
}