<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01035v2 extends CardReaction
{
    // WHY public: stRecruitComputeDiscount reads this through the owner's
    // getParleyDiscount after the reaction window. It has to survive the
    // playerReaction request boundary (card is serialized with the flag).
    public bool $ParleyBonusArmed = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gain +2 Influence when Parleying");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may gain +2 Influence for this Parley: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Gain +2 Influence'), 'gainInfluence');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionResolved || $event instanceof EventPlayerTurnEnd)
        {
            // WHY: setUsed already wrote ParleyBonusArmed=true to the DB. Clearing the
            // field in memory is not enough — the next turn reloads that row and the
            // discount still applies. IsUpdated is what runEvents flushes.
            if (! $this->ParleyBonusArmed)
            {
                return;
            }

            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null && $event->playerId == $owner->ControllerId)
            {
                $this->ParleyBonusArmed = false;
                $owner->IsUpdated = true;
            }
            return;
        }

        if (! $event instanceof EventEnteringPayState || ! $this->isAvailable())
        {
            return;
        }

        if ($event->payStateType != Game::PAY_STATE_RECRUIT_MERCENARY)
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! $event->theah->cardInCity($owner))
        {
            return;
        }

        if ($event->playerId != $owner->ControllerId)
        {
            return;
        }

        $performerId = (int) $event->theah->game->globals->get(Game::CHOSEN_PERFORMER, 0);
        if ($performerId != $owner->Id)
        {
            return;
        }

        if (! $event->theah->game->globals->get(Game::PERFORMER_PARLEYED, false))
        {
            return;
        }

        $recruit = $event->theah->game->getCardObjectFromDb($event->cardId);
        if (! $recruit instanceof CityCharacter || ! $recruit->Negotiable)
        {
            return;
        }

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'gainInfluence')
        {
            $owner = $this->getOwningCharacter($game->theah);
            if ($owner !== null && $game->theah->cardInCity($owner))
            {
                $this->ParleyBonusArmed = true;
                $this->setUsed($game->theah, true);

                $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} uses City Reaction and gains +2 Influence for this Parley.'), [
                    'owner_inject_code' => $owner->getInjectCode(),
                    'player_name' => $game->getPlayerNameById($owner->ControllerId),
                ]);
            }
        }

        $game->gamestate->nextState("done");
    }
}
