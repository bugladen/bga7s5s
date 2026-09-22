<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04035 extends CardReaction
{
    private string $location = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Add +1 to Your Pressure Total per Academic");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        $location = $this->location !== '' ? $this->location : $theah->game->translate('the pressured location');
        return $base . sprintf($theah->game->translate('${you} may add +1 to your total for each of your Academics at %s: '), $location);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Add Academic Bonus'), 'use');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPressureOccuring && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner == null)
            {
                return;
            }

            // WHY: "your performer's location" — you are the pressuring player (your performer is at $event->location).
            if ($event->playerId != $owner->ControllerId)
            {
                return;
            }

            if ($this->countAcademicsAtLocation($event->theah, $owner->ControllerId, $event->location) < 1)
            {
                return;
            }

            $this->location = $event->location;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId === 'use' && $this->location !== '')
        {
            // WHY: Flag is read later in pressureLocation() after reactions resolve (Loyal / Solomonia pattern).
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::MEETING_OF_THE_MINDS_PRESSURE_TYPE);
            $game->globals->set(Game::MEETING_OF_THE_MINDS_PLAYER_ID, $owner->ControllerId);

            $count = $this->countAcademicsAtLocation($game->theah, $owner->ControllerId, $this->location);
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to add +${count} to their total for this Pressure (${count} Academic(s) at ${location}).'), [
                "i18n" => ["location"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "count" => $count,
                "location" => $this->location,
            ]);

            $this->setUsed($game->theah, true);
        }

        $this->location = '';
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }

    private function countAcademicsAtLocation(Theah $theah, int $playerId, string $location): int
    {
        $count = 0;
        foreach ($theah->getCharactersAtLocation($location) as $character)
        {
            if ($character->ControllerId == $playerId && $character->hasTrait("Academic"))
            {
                $count++;
            }
        }

        return $count;
    }
}
