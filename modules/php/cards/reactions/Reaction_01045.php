<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01045 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gain Reknown at High Drama End");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to gain 1 Reknown at the end of the High Drama: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Gain Reknown'), 'gainReknown');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventHighDramaPhaseEnd)
        {
            $soe = $this->getOwningCard($event->theah);
            if ($soe->Location == Game::LOCATION_PLAYER_HOME)
            {
                //See if there are any available attachments or available mercenaries in the city
                $found = false;
                $locations = $event->theah->getCityLocations();
                foreach ($locations as $location)
                {
                    $attachments = $event->theah->getAvailableAttachmentsAtLocation($location->Name);
                    $characters = $event->theah->getCharactersAtLocation($location->Name);
                    $mercenarys = array_filter($characters, fn($character) => $character->isMercenary() && ! $character->isControlled());

                    if (count($mercenarys) > 0 || count($attachments) > 0)
                    {
                        $found = true;
                        break;
                    }
                }

                if ( ! $found)
                {
                    $transition = EventFactory::createReactionTransitionEvent($soe->ControllerId, $soe->Id, $this->Id);
                    $event->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'gainReknown')
        {
            $soe = $this->getOwningCard($game->theah);
            $event = EventFactory::createPlayerGainsReknownEvent($soe->ControllerId, 1);
            $game->theah->queueEvent($event);

            $game->notifyAllPlayers("message", clienttranslate('<strong>Song of Eisen</strong>: ${player_name} activates Reaction and gains 1 Reknown'), [
                'player_name' => $game->getPlayerNameById($soe->ControllerId),
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}