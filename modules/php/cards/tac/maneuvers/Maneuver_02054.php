<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_02054 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Equip to participant from dueling line");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        $owner = $this->getOwningAttachment($theah);
        $actor = $theah->getDuelRoundActor();
        [$hasRestrictions, $restrictionExplanation] = $theah->game->hasEquipRestrictions($actor, $owner);
        if ($hasRestrictions || ! $owner->canAttachTo($actor))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningAttachment($event->theah);
            $actor = $event->theah->getDuelRoundActor();

            $actualTargetId = $owner->getRequiredAttachTargetId($event->theah, $actor->Id);
            $equipEvent = EventFactory::createAttachmentEquippedEvent(
                $actor->ControllerId, $actualTargetId, $owner->Id,
                $owner->WealthCost, 0, false, '', false, $owner->Id, $this->Id
            );
            $event->theah->queueEvent($equipEvent);

            if ($actor->ModifiedCombat <= 1)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($actor->ControllerId, $owner->getInjectCode());
                $event->theah->queueEvent($drawEvent);
            }
        }
    }
}
