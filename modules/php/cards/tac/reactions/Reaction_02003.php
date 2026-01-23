<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02003 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Intervene when Strega at Mourad's Location is Challenged");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('A Strega at Mourad\'s Location is being Challenged. ${you} may choose to Intervene: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Intervene'), 'intervene');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            $target = $event->theah->getCharacterById($event->defenderId);
            if ($target->ControllerId == $owner->ControllerId && 
            $target->Location == $owner->Location && 
            $event->theah->cardInCity($owner) && 
            $target->hasTrait("Strega"))
            {
                $reactionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'intervene')
        {
            $owner = $this->getOwningCard($game->theah);
            $game->theah->interventionCheck($owner);
            
            $target = $game->theah->getCardById($game->globals->get(GAME::CHOSEN_TARGET));
            $target->removeCondition(Game::DUEL_DEFENDER);
            $owner->addCondition(Game::DUEL_DEFENDER);
            $game->globals->set(Game::CHOSEN_TARGET, $owner->Id);
    
            $interveneEvent = EventFactory::createCharacterIntervenedEvent($owner->ControllerId, $target->Id, $owner->Id);
            $game->theah->eventCheck($interveneEvent);
            $game->theah->queueEvent($interveneEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction and INTERVENES in the Challenge.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $game->globals->set(GAME::CHALLENGE_ACCEPTED, true);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}