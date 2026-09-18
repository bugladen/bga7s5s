<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03039 extends Technique
{
    private bool $MoveHome = false;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("-2 Thrust; Adversary Discards; Maybe En Garde; Move Home");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        // "(Your combat card must have at least 2 [Thrust].)" — same gate shape as Technique_01050's -1 Thrust.
        if ($theah->getCurrentRoundThrust() < 2)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();

            // WHY: Move Home is unconditional once the technique resolves — only the En Garde
            // clause is gated on post-discard hand sizes. Flag here so EndOfRound always fires.
            $this->MoveHome = true;
            $owner->IsUpdated = true;

            $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
            if (count($hand) > 0)
            {
                // Adversary chooses which card to discard (Maya Technique_01093 pattern).
                $transition = EventFactory::createTransitionEvent($adversary->ControllerId, $owner->Id, "03039", $this->Id);
                $event->theah->queueEvent($transition);
            }
            else
            {
                // No discard possible — still evaluate En Garde against current (empty) hand.
                $this->maybeEnGardeInigo($event->theah, $owner, count($hand));
            }

            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $event->thrust -= 2;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s: Technique [%s] subtracts 2 Thrust."),
                $owner->getInjectCode(),
                $this->Name
            );
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->MoveHome = false;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound && $this->MoveHome)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $this->MoveHome = false;
            $owner->IsUpdated = true;

            if (
                ! $event->theah->game->characterIsInDiscardOrLocker($owner)
                && $owner->Location != Game::LOCATION_PLAYER_HOME
            )
            {
                $event->theah->game->notify->all("message", clienttranslate('${technique_inject_code}: ${player_name} moves ${character_inject_code} Home.'), [
                    "technique_inject_code" => $owner->getInjectCode(),
                    "player_name" => $event->theah->game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $owner->getInjectCode(),
                ]);

                // WHY: engage=false — text says "move Íñigo Home" with no Engage printed (contrast _01053).
                $moveEvent = EventFactory::createCardMovingEvent(
                    $owner->ControllerId,
                    $owner->Id,
                    $owner->Location,
                    Game::LOCATION_PLAYER_HOME,
                    $engage = false,
                    $owner->Id,
                    $this->Id
                );
                $event->theah->queueEvent($moveEvent);
            }
        }

        if ($event instanceof EventDuelEnd && $this->MoveHome)
        {
            $this->MoveHome = false;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03039)
        {
            $card = $game->getCardObjectFromDb($id);

            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $playerId = $game->getActivePlayerId();

            if ($card->ControllerId != $playerId)
            {
                throw new \BgaUserException($game->translate("You do not control this card"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new \BgaUserException($game->translate("Card not in your hand"));
            }

            $owner = $this->getOwningCharacter($game->theah);
            $adversary = $game->theah->getDuelRoundOpponent();

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $card->OwnerId,
                $card->Id,
                $owner->Id,
                $asPayment = false,
                $asPlayed = false,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            // WHY: Discard is queued, not flushed yet — compute post-discard hand size as count-1
            // so the printed "Then, if they have more cards…" gate sees the correct totals.
            $adversaryHandAfter = count($game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId)) - 1;
            $this->maybeEnGardeInigo($game->theah, $owner, $adversaryHandAfter);

            $game->gamestate->nextState();
        }
    }

    private function maybeEnGardeInigo(Theah $theah, $owner, int $adversaryHandCount): void
    {
        $ownerHandCount = count($theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId));
        if ($adversaryHandCount > $ownerHandCount)
        {
            $engardeEvent = EventFactory::createCardEngardedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
            $theah->queueEvent($engardeEvent);
        }
    }
}
