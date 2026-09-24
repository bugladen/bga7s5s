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

            if ($event->cardId == $owner->Id && $owner instanceof _01133 && $owner->Location == Game::LOCATION_HAND)
            {
                $owner->WillEngage = false;
                $owner->IsUpdated = true;
            }

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

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId == 'engage' && $owner instanceof _01133)
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

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $performerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            // WHY: EventEnteringPayState has runEventHubAfterCards=true. Cards stack this
            // Reaction Transition first, then EventHub stackEvent's CalculatePayDiscount —
            // which gets a lower priority and runs BEFORE the Transition. That early calc
            // sees WillEngage=false. Recalc now that Engage set WillEngage (mirror
            // Action_03060 / Reaction_01116b / Reaction_03013).
            $game->theah->calculateInHandPayDiscount(
                $owner->ControllerId,
                Game::PAY_STATE_IN_HAND_ACTION,
                $owner->Id,
                $this->Id
            );
        }

        // WHY: both Engage and Pass — after the engage chooser, Back on pay must not
        // return to choosePerformer (would re-enter EnteringPayState / re-prompt).
        $game->globals->set(Game::ABNORMAL_FLOW, true);

        $game->gamestate->nextState("done");
    }
}