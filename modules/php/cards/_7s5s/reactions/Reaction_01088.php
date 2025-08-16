<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01088 extends RiskReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Mercenary Challenge");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Cancel Mercenary Challenge: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Challenge'), 'cancelChallenge');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && $this->isAvailable())
        {
            $risk = $this->getOwningCard($event->theah);
            if ($risk->Location == Game::LOCATION_HAND)
            {
                $game = $event->theah->game;
                $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
                $performer = $game->theah->getCharacterById($performerId);

                if ($performer->hasTrait("Mercenary") && $performer->isNotControlledByPlayer($risk->ControllerId))
                {
                    $transition = EventFactory::createReactionTransitionEvent($risk->ControllerId, $risk->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancelChallenge')
        {
            $game->gamestate->nextState("pay");
            return;
        }

        $game->gamestate->nextState('done');
    }

    public function reactionPaidFor(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::reactionPaidFor($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancelChallenge')
        {
            $owner = $this->getOwningCard($game->theah);
            $game->notifyAllPlayers("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and cancelled the Mercenary Challenge.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $game->globals->set(Game::CHALLENGE_CANCELLED, true);
        }
    }
}