<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02039 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Add Threat');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may add a threat to both participants: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Add Threat'), 'addThreat');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCombatCardAnnounced && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location != Game::LOCATION_HAND)
                return;

            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL, false);
            if (! $inDuel)
                return;

            if ($event->playerId == $owner->ControllerId)
                return;

            $owner->IsUpdated = true;
            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'addThreat')
        {
            $owner = $this->getOwningCard($game->theah);
            $this->setUsed($game->theah, true);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} uses Reaction to add a threat to both participants.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $threatEvent = EventFactory::createThreatModifiedEvent(1, 1);
            $game->theah->queueEvent($threatEvent);
        }

        if ($reactionId == 'pass')
        {
            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
