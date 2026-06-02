<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01117 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Remove Renown from Ekaterina's Location after Opponent Claims it.");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to remove a Renown from Ekaterina\'s Location after an Opponent claims it: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Remove Renown'), 'removeReknown');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventLocationClaimed && $this->isAvailable())
        {
            $ekaterina = $this->getOwningCharacter($event->theah);
            $location = $event->theah->getCityLocation($event->location);
            if ($event->playerId != $ekaterina->ControllerId && $ekaterina->Location == $event->location && $location->Renown > 0)
            {
                $transition = EventFactory::createReactionTransitionEvent($ekaterina->ControllerId, $ekaterina->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            $ekaterina = $this->getOwningCharacter($game->theah);
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction to remove a Renown from Ekaterina\'s Location after an Opponent claims it.'), [
                "player_name" => $game->getPlayerNameById($ekaterina->ControllerId),
                "reaction_inject_code" => $ekaterina->getInjectCode(),
            ]);

            $ekaterina = $this->getOwningCharacter($game->theah);
            $reknownRemovedEvent = EventFactory::createRenownRemovedFromLocationEvent($ekaterina->ControllerId, $ekaterina->Location, 1, $ekaterina->getInjectCode());
            $game->theah->eventCheck($reknownRemovedEvent);
            $game->theah->queueEvent($reknownRemovedEvent);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}