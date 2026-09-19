<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01015 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Draw Card after Character is Destroyed');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may draw a card after a Character is destroyed: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Draw Card'), 'drawCard');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterDestroyed && $this->isAvailable())
        {
            $scheme = $this->getOwningCard($event->theah);
            // WHY: Chosen schemes sit at Home until dusk locker. After CardSentToLocker
            // the card can still be in $theah->cards for the rest of that request
            // (buildCity does not reload mid-run). Without this gate, a destroy that
            // lands in the same dusk cleanup batch as scheme-to-locker still queues
            // the draw prompt after the scheme has already left play.
            if ($scheme === null || $scheme->Location != Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            $reactionEvent = EventFactory::createReactionTransitionEvent($scheme->ControllerId, $scheme->Id, $this->Id);
            $event->theah->queueEvent($reactionEvent);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "drawCard")
        {
            $scheme = $this->getOwningCard($game->theah);
            $event = EventFactory::createCardDrawnEvent($scheme->ControllerId, sprintf($game->translate("%s effect"), $scheme->getInjectCode()));
            $game->theah->queueEvent($event);

            $game->notifyAllPlayers("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to draw a card.'), [
                "reaction_inject_code" => $scheme->getInjectCode(),
                "player_name" => $game->getPlayerNameById($scheme->ControllerId),
            ]);

            $this->setUsed($game->theah, true);
            // WHY: Multiple destroys before the first response can queue several
            // transitions. Once Used, the framework skip should no-op them, but
            // deleting is the same discipline as Reaction_01182 / Reaction_01181.
            $game->theah->deleteTransitionEvents($this->Id);
        }

        $game->gamestate->nextState("done");
    }
}
