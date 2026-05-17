<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03005 extends CardReaction
{
    private string $location = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Claim Location After Red Hand's Challenge is Refused");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('Your Red Hand\'s challenge was refused. ${you} may claim the location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Claim %s'), $this->location), 'claim');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeRejected && $this->isAvailable())
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

            if (! $challenger->hasTrait("Red Hand"))
            {
                return;
            }

            $cityLocation = $event->theah->getCityLocation($challenger->Location);
            if ($cityLocation == null)
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

        if ($reactionId == 'claim' && $this->location != '')
        {
            $claimEvent = EventFactory::createLocationClaimedEvent($owner->ControllerId, null, $this->location);
            $game->theah->eventCheck($claimEvent);
            $game->theah->queueEvent($claimEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to Claim ${location_name}.'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "location_name" => $this->location,
            ]);

            $this->setUsed($game->theah, true);
        }

        $this->location = '';
        $owner->IsUpdated = true;

        $game->gamestate->nextState("done");
    }
}
