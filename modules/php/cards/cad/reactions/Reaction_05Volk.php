<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Volk extends CardReaction
{
    // WHY: Stash destination at trigger time — by performReaction the mover may have
    // moved again or Location reads may differ; Horatio Reaction_01066 same shape.
    private string $FollowedCharacterLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Follow Opposing Character";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP (Cooper).
        return parent::getReactionDescription($theah)
            . '${you} may move Volk to the opposing character\'s new location: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, 'Follow', 'followCharacter');
        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoved) || ! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null)
        {
            return;
        }

        // WHY: Plain Reaction (not Horatio City Reaction). In play = city or Home.
        if (! $event->theah->cardInPlay($owner))
        {
            return;
        }

        $character = $event->theah->getCardById($event->cardId);
        if (! ($character instanceof Character))
        {
            return;
        }

        // WHY: Playtests — "opposing" here is enemy-controlled, not leave-from-here.
        // Horatio needs fromLocation == owner (City + co-located). Volk follows any
        // enemy move into a play location, including Home → city while Volk is elsewhere.
        if ($character->ControllerId == 0 || $character->ControllerId == $owner->ControllerId)
        {
            return;
        }

        $fromLocation = $event->fromLocation;
        if ($fromLocation != Game::LOCATION_PLAYER_HOME
            && ! $event->theah->locationInCity($fromLocation))
        {
            return;
        }

        // WHY: Any city destination or Home. Skip discard/locker so Follow is never dead.
        $toLocation = $event->toLocation;
        if ($toLocation != Game::LOCATION_PLAYER_HOME
            && ! $event->theah->locationInCity($toLocation))
        {
            return;
        }

        // WHY: Already there — "move to their new location" would be a no-op prompt.
        if ($toLocation == $owner->Location)
        {
            return;
        }

        $this->FollowedCharacterLocation = $toLocation;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'followCharacter')
        {
            $owner = $this->getOwningCharacter($game->theah);
            $destination = $this->FollowedCharacterLocation;

            if ($owner !== null
                && $destination !== ''
                && $destination != $owner->Location
                && ($destination == Game::LOCATION_PLAYER_HOME || $game->theah->locationInCity($destination)))
            {
                $game->notify->all("message", '${reaction_inject_code}: ${player_name} used Reaction and followed to ${location_name}.', [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "location_name" => $destination,
                ]);

                $moveEvent = EventFactory::createCardMovingEvent(
                    $owner->ControllerId,
                    $owner->Id,
                    $owner->Location,
                    $destination,
                    false,
                    $owner->Id,
                    $this->Id
                );
                $game->theah->queueEvent($moveEvent);

                $this->setUsed($game->theah, true);
            }
        }

        $game->gamestate->nextState("done");
    }
}
