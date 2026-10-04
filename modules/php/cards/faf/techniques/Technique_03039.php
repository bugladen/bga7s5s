<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03039 extends Technique
{
    private bool $MoveHome = false;

    // WHY: Printed "Then, if they have more…" means compare AFTER the adversary discard
    // lands — not when the picker confirms. Flag so EventCardDiscardedFromHand does the
    // hand-size En Garde gate; Move Home stays separate (flagged on resolve).
    private bool $PendingEnGardeCheck = false;

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

            // WHY: Move Home is unconditional once the technique resolves — not gated on
            // whether the adversary had a card to discard or on the En Garde hand check.
            $this->MoveHome = true;
            $owner->IsUpdated = true;

            $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
            if (count($hand) > 0)
            {
                // Adversary chooses which card to discard (Maya Technique_01093 pattern).
                // En Garde check waits for EventCardDiscardedFromHand (PendingEnGardeCheck).
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

        // WHY: Discard uses runEventHubAfterCards — card handlers see the card still in
        // hand. Exclude event->cardId so the compare is post-discard (InigoHand <
        // adversaryHandAfter). Engarde event is queued and runs after this discard finishes.
        if (
            $event instanceof EventCardDiscardedFromHand
            && $this->PendingEnGardeCheck
            && ! $event->canceled
            && $event->asEffect
        )
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null && $event->sourceId == $owner->Id)
            {
                $this->PendingEnGardeCheck = false;
                $owner->IsUpdated = true;

                $adversary = $event->theah->getDuelRoundOpponent();
                $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
                $adversaryHandAfter = 0;
                foreach ($hand as $handCard)
                {
                    if ($handCard->Id != $event->cardId)
                    {
                        $adversaryHandAfter++;
                    }
                }
                $this->maybeEnGardeInigo($event->theah, $owner, $adversaryHandAfter);
            }
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
            $this->PendingEnGardeCheck = false;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound && $this->MoveHome)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $this->MoveHome = false;
            $this->PendingEnGardeCheck = false;
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

        if ($event instanceof EventDuelEnd && ($this->MoveHome || $this->PendingEnGardeCheck))
        {
            $this->MoveHome = false;
            $this->PendingEnGardeCheck = false;
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

            // WHY: Arm the post-discard En Garde check before queueing discard. Do not
            // compare hands here — discard is not flushed yet; handleEvent on
            // EventCardDiscardedFromHand owns the "Then…" gate.
            $this->PendingEnGardeCheck = true;
            $owner->IsUpdated = true;
            $game->updateCardObjectInDb($owner);

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $card->OwnerId,
                $card->Id,
                $owner->Id,
                $asPayment = false,
                $asPlayed = false,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();
        }
    }

    private function maybeEnGardeInigo(Theah $theah, $owner, int $adversaryHandCount): void
    {
        // WHY: "if they have more cards in hand than you" = adversaryHand > InigoHand
        // (same as InigoHand < adversaryHand). Mandatory when true — printed effect, not a chooser.
        $ownerHandCount = count($theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId));
        if ($adversaryHandCount > $ownerHandCount)
        {
            $engardeEvent = EventFactory::createCardEngardedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
            $theah->queueEvent($engardeEvent);
        }
    }
}
