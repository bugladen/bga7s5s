<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03037 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Collect Renown After Sanjay's Challenge is Refused");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may Collect a Renown from Sanjay\'s location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Collect Renown'), 'collect');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeRejected && $this->isAvailable())
        {
            $sanjay = $this->getOwningCharacter($event->theah);
            if ($sanjay == null || $event->challengerId != $sanjay->Id)
            {
                return;
            }

            // WHY: no Renown at the location → Collect would be a no-op prompt.
            $cityLocation = $event->theah->getCityLocation($sanjay->Location);
            if ($cityLocation == null || $cityLocation->Renown < 1)
            {
                return;
            }

            $transition = EventFactory::createReactionTransitionEvent($sanjay->ControllerId, $sanjay->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'collect')
        {
            $sanjay = $this->getOwningCharacter($game->theah);
            $cityLocation = $game->theah->getCityLocation($sanjay->Location);

            if ($cityLocation != null && $cityLocation->Renown > 0)
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to Collect a Renown from ${location_name}.'), [
                    "i18n" => ["location_name"],
                    "reaction_inject_code" => $sanjay->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($sanjay->ControllerId),
                    "location_name" => $sanjay->Location,
                ]);

                $removeEvent = EventFactory::createRenownRemovedFromLocationEvent(
                    $sanjay->ControllerId,
                    $sanjay->Location,
                    1,
                    $sanjay->getInjectCode()
                );
                $game->theah->queueEvent($removeEvent);

                $gainEvent = EventFactory::createPlayerGainsReknownEvent($sanjay->ControllerId, 1);
                $game->theah->queueEvent($gainEvent);

                $this->setUsed($game->theah, true);
            }
        }

        $game->gamestate->nextState("done");
    }
}
