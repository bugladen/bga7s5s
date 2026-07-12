<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03040 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Soline to any City location after a character moves here");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to move Soline to any City location: ');
    }

    /**
     * @return string[] city location names Soline can move to (excludes her current location)
     */
    private function getEligibleDestinations(Theah $theah, Character $owner): array
    {
        $destinations = [];
        foreach (array_keys($theah->getCityLocations()) as $locationName)
        {
            if ($locationName != $owner->Location)
            {
                $destinations[] = $locationName;
            }
        }
        return $destinations;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            foreach ($this->getEligibleDestinations($theah, $owner) as $locationName)
            {
                $array[] = $this->createButtonProperty(
                    $theah->game,
                    sprintf($theah->game->translate('Move Soline to %s'), $locationName),
                    "moveTo-$locationName"
                );
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoved)) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;
        if (! $event->theah->cardInCity($owner)) return;
        if ($event->cardId == $owner->Id) return;
        if ($event->toLocation != $owner->Location) return;

        $character = $event->theah->getCardById($event->cardId);
        if (! ($character instanceof Character)) return;
        if ($character->ControllerId == 0) return;

        // WHY: Printed text is "a character", not "opposing" — allies arriving also trigger.
        // Do NOT add the Ise enemy-controller gate.

        if (count($this->getEligibleDestinations($event->theah, $owner)) == 0) return;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass' && str_starts_with($reactionId, 'moveTo-'))
        {
            $locationName = substr($reactionId, strlen('moveTo-'));
            $soline = $this->getOwningCharacter($game->theah);

            $valid = $this->getEligibleDestinations($game->theah, $soline);
            if (in_array($locationName, $valid, true))
            {
                $moveEvent = EventFactory::createCardMovingEvent(
                    $soline->ControllerId,
                    $soline->Id,
                    $soline->Location,
                    $locationName,
                    false,
                    $soline->Id,
                    $this->Id
                );
                $game->theah->queueEvent($moveEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and moved to ${location_name}.'), [
                    "reaction_inject_code" => $soline->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($soline->ControllerId),
                    "location_name" => $locationName,
                ]);

                $this->setUsed($game->theah, true);
            }
        }

        $game->gamestate->nextState("done");
    }
}
