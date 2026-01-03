<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatDependsOnNotBeingFirstPlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01090 extends RiskReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Resolve Your Ability as if you were not First Player");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may resolve your ability as if you were not First Player: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Resolve Ability as if not First Player'), 'resolveAbility');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $this->isAvailable())
        {
            $card = $event->theah->getCardById($event->sourceId);
            $ability = $card->getAbilityById($event->actionId);
            if ($ability instanceof IAbilityThatDependsOnNotBeingFirstPlayer)
            {
                $owner = $this->getOwningCard($event->theah);
                $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->stackEvent($transitionEvent);
            }
        }

        if ($event instanceof EventPlayerTurnEnd)
        {
            $game = $event->theah->game;
            $extraActions = $game->globals->get(Game::EXTRA_ACTIONS, 0);
            $overrideActive = $game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);
            if ($extraActions > 0 && $overrideActive)
            {
                $game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'resolveAbility')
        {
            $game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("overrideAsNotFirstPlayer", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and will resolve their ability as if they were not the First Player.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
            ]);
        }

        $game->gamestate->nextState("done");
    }
}

