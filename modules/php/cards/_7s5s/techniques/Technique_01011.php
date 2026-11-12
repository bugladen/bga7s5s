<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01011 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Thrust for each OTHER Red Hand at same location");
    }

    /**
     * Other Red Hands you control at $location (excludes $excludeId — typically Servo).
     *
     * @return Character[]
     */
    private function getOtherControlledRedHandsAtLocation(Theah $theah, string $location, int $controllerId, int $excludeId): array
    {
        $characters = $theah->getCharactersAtLocation($location);
        return array_values(array_filter(
            $characters,
            fn($character) => $character->Id != $excludeId
                && $character->ControllerId == $controllerId
                && $character->hasTrait("Red Hand")
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // WHY not IN_DUEL: also offered at challenge ActivateTechnique and applies via
        // EventGenerateChallengeThreat. Hide only when +0 (no other controlled Red Hands).
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return false;
        }

        return count($this->getOtherControlledRedHandsAtLocation(
            $theah,
            $owner->Location,
            $owner->ControllerId,
            $owner->Id
        )) > 0;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            // WHY: Dead challenger is in Locker; Red Hands are still at the challenge city site.
            $location = $event->theah->game->characterIsInDiscardOrLocker($actor)
                ? $event->theah->game->globals->get(Game::CHOSEN_LOCATION, $actor->Location)
                : $actor->Location;
            $redHands = $this->getOtherControlledRedHandsAtLocation(
                $event->theah,
                $location,
                $actor->ControllerId,
                $actor->Id
            );

            $event->adversaryThreat += count($redHands);
            $event->explanations[] = $event->theah->game->translate("+1 Threat for each OTHER Red Hand at same location");
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            $redHands = $this->getOtherControlledRedHandsAtLocation(
                $event->theah,
                $actor->Location,
                $actor->ControllerId,
                $actor->Id
            );

            $event->thrust += count($redHands);
            $event->explanations[] = $event->theah->game->translate("+1 Thrust for each OTHER Red Hand at same location");
        }
    }
}
