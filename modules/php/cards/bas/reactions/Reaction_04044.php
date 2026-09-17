<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04044 extends CardReaction
{
    private string $location = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Make performer's location uncontrolled");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        $location = $this->location !== ''
            ? $this->location
            : $theah->game->translate('their location');

        return $base . sprintf(
            $theah->game->translate('Your Leader issued a challenge. ${you} may make %s uncontrolled: '),
            $location
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $label = $this->location !== ''
            ? sprintf($theah->game->translate('Make %s Uncontrolled'), $this->location)
            : $theah->game->translate('Make Location Uncontrolled');

        $array[] = $this->createButtonProperty($theah->game, $label, 'uncontrol');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && $this->isAvailable() && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner == null)
            {
                return;
            }

            $challenger = $event->theah->getCharacterById($event->challengerId);
            if ($challenger == null)
            {
                return;
            }

            if ($challenger->ControllerId != $owner->ControllerId)
            {
                return;
            }

            // WHY: Leader Reaction = mechanical performer-trait gate (not Sorcerer).
            if (! $challenger->hasTrait("Leader"))
            {
                return;
            }

            $cityLocation = $event->theah->getCityLocation($challenger->Location);
            if ($cityLocation == null || $cityLocation->Controller == 0)
            {
                return;
            }

            if (! $event->theah->canLocationBecomeUncontrolledBy($owner->ControllerId, $challenger->Location))
            {
                return;
            }

            $this->location = $challenger->Location;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId == 'uncontrol' && $this->location != '')
        {
            if ($game->theah->canLocationBecomeUncontrolledBy($owner->ControllerId, $this->location))
            {
                $cityLocation = $game->theah->getCityLocation($this->location);
                if ($cityLocation !== null && $cityLocation->Controller != 0)
                {
                    $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent(
                        $owner->ControllerId,
                        $this->location
                    );
                    $game->theah->queueEvent($uncontrolledEvent);

                    $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to make ${location_name} uncontrolled.'), [
                        "i18n" => ["location_name"],
                        "reaction_inject_code" => $owner->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($owner->ControllerId),
                        "location_name" => $this->location,
                    ]);

                    $this->setUsed($game->theah, true);
                }
                else
                {
                    $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${location_name} is already uncontrolled.'), [
                        "i18n" => ["location_name"],
                        "reaction_inject_code" => $owner->getInjectCode(),
                        "location_name" => $this->location,
                    ]);
                }
            }
            else
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${location_name} cannot become uncontrolled.'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "location_name" => $this->location,
                ]);
            }
        }

        $this->location = '';
        $owner->IsUpdated = true;

        $game->gamestate->nextState("done");
    }
}
