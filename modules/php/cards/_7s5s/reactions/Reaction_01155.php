<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01155 extends RiskReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Equip to Faction Hand from Duel Card Line");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to equip this card to your Faction Hand from the Duel card line: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Equip'), 'equip');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelEndOfRound)
        {
            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL);
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_PURGATORY && $inDuel)
            {
                $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'equip')
        {
            $game->notifyAllPlayers('message', clienttranslate('Improvised Weapon: ${player_name} has returned to their Faction Hand from the Duel card line'), [
                "player_name" => $game->getActivePlayerName(),
            ]);

            $owner = $this->getOwningCard($game->theah);
            $moveEvent = EventFactory::createCardAddedToHandEvent($owner->ControllerId, $owner->Id);
            $game->theah->queueEvent($moveEvent);

        }

        $game->gamestate->nextState("done");
    }
}