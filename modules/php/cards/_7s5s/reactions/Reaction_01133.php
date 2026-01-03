<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01133 extends RiskReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Choose if Performer Engages");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose if your Performer Engages to ignore cost of this Risk: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Engage'), 'engage');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventEnteringPayState)
        {
            $owner = $this->getOwningCard($event->theah);
            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            if ($performerId != null)
            {
                $performer = $event->theah->getCharacterById($performerId);
                if ($event->cardId == $owner->Id && ! $performer->Engaged)
                {
                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->stackEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'engage')
        {
            $owner = $this->getOwningCard($game->theah);
            if ($owner instanceof _01133)
            {
                $owner->WillEngage = true;
                $owner->IsUpdated = true;

                $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
                $performer = $game->theah->getCharacterById($performerId);

                $game->notify->all("message", clienttranslate('${player_name} chooses to engage ${character_inject_code} to pay for the cost of ${card_inject_code}'), [
                    'player_name' => $game->getPlayerNameById($owner->ControllerId),
                    'character_inject_code' => $performer->getInjectCode(),
                    'card_inject_code' => $owner->getInjectCode(),
                ]);

                $game->globals->set(Game::ABNORMAL_FLOW, true);

                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $performerId, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }
        }

        $game->gamestate->nextState("done");
    }
}