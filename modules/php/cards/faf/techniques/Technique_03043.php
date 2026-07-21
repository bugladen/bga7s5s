<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03043 extends Technique
{
    /** @var int[] All revealed card ids (shown in the multiplayer acknowledge state). */
    private array $revealedCardIds = [];

    /** @var int[] Revealed cards that are Attachments (eligible for discard). */
    private array $revealedAttachmentIds = [];

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Reveal Hand; Discard Revealed Attachment");
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

        // Gambling Technique: actor must have gambled for their combat card this round.
        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null)
        {
            return false;
        }

        if ($actor->ModifiedFinesse <= $adversary->ModifiedFinesse)
        {
            return false;
        }

        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
        return count($hand) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();
            $game = $event->theah->game;

            $deck = $game->getGameDeckObject();
            $hand = array_values($deck->getCardsInLocation(Game::LOCATION_HAND, $adversary->ControllerId));

            $count = min(2, count($hand));
            $this->revealedCardIds = [];
            $this->revealedAttachmentIds = [];

            if ($count > 0)
            {
                $keys = (array) array_rand($hand, $count);
                foreach ($keys as $key)
                {
                    $card = $game->getCardObjectFromDb($hand[$key]['id']);
                    $event->theah->addCardToWorld($card);

                    $game->notify->all("message",
                        clienttranslate('${card_inject_code} reveals ${picked_card} from <strong>${player_name}</strong>\'s hand.'),
                        [
                            "card_inject_code" => $attachment->getInjectCode(),
                            "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                            "picked_card" => $card->getInjectCode(),
                            "card" => $card->getPropertyArray($game),
                        ]);

                    $this->revealedCardIds[] = $card->Id;
                    if ($card instanceof Attachment)
                    {
                        $this->revealedAttachmentIds[] = $card->Id;
                    }
                }

                $attachment->IsUpdated = true;

                // WHY: Multiplayer acknowledge (Constanzo SETUP_TABLE_01006_2 / Lorenzo 01090)
                // so every player sees the revealed cards in chooseList, not only the log.
                $game->globals->set(Game::MULTI_STATE_INITIATING_PLAYER, $attachment->ControllerId);

                // WHY: sourceId = attachment (technique host), not equipped character —
                // actFromCardWithId / getArgs look up techniques on the source card.
                $transition = EventFactory::createTransitionEvent(
                    $attachment->ControllerId,
                    $attachment->Id,
                    "03043",
                    $this->Id
                );
                $event->theah->queueEvent($transition);
            }
            else
            {
                $game->notify->all("message",
                    clienttranslate('${card_inject_code}: ${player_name}\'s hand is empty — no cards to reveal.'),
                    [
                        "card_inject_code" => $attachment->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                    ]);
            }

            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->clearRevealed($event->theah);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03043)
        {
            $cards = [];
            foreach ($this->revealedCardIds as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card !== null)
                {
                    $cards[] = $card->getPropertyArray($game);
                }
            }
            $args["cards"] = $cards;
        }

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03043_3)
        {
            $args["cardIds"] = $this->revealedAttachmentIds;
        }

        return $args;
    }

    public function stateFromTechnique(Game $game, int $state, string $stateName): void
    {
        parent::stateFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03043_2)
        {
            $attachment = $this->getOwningCard($game->theah);
            $attachmentCount = count($this->revealedAttachmentIds);

            if ($attachmentCount == 0)
            {
                $game->notify->all("message",
                    clienttranslate('${card_inject_code}: None of the revealed cards were attachments — no discard.'),
                    [
                        "card_inject_code" => $attachment->getInjectCode(),
                    ]);
                $this->clearRevealed($game->theah);
                $game->gamestate->nextState("done");
                return;
            }

            if ($attachmentCount == 1)
            {
                // WHY: Single revealed attachment — choice is forced; skip the picker.
                $this->discardRevealedAttachment($game->theah, $this->revealedAttachmentIds[0]);
                $this->clearRevealed($game->theah);
                $game->gamestate->nextState("done");
                return;
            }

            // WHY: Adversary must pick which of the two revealed attachments to discard.
            $adversary = $game->theah->getDuelRoundOpponent();
            $game->gamestate->changeActivePlayer($adversary->ControllerId);
            $game->gamestate->nextState("discard");
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03043_3)
        {
            if (! in_array($id, $this->revealedAttachmentIds, true))
            {
                throw new UserException($game->translate("You must discard one of the revealed attachments."));
            }

            $card = $game->getCardObjectFromDb($id);

            if ($card == null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            $playerId = $game->getActivePlayerId();

            if ($card->ControllerId != $playerId)
            {
                throw new UserException($game->translate("You do not control this card"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new UserException($game->translate("Card not in your hand"));
            }

            if (! ($card instanceof Attachment))
            {
                throw new UserException($game->translate("Card is not an attachment"));
            }

            $this->discardRevealedAttachment($game->theah, $id);
            $this->clearRevealed($game->theah);

            $game->gamestate->nextState();
        }
    }

    private function discardRevealedAttachment(Theah $theah, int $cardId): void
    {
        $attachment = $this->getOwningCard($theah);
        $card = $theah->game->getCardObjectFromDb($cardId);

        $theah->game->notify->all("message",
            clienttranslate('${card_inject_code}: ${player_name} discards revealed attachment ${picked_card}.'),
            [
                "card_inject_code" => $attachment->getInjectCode(),
                "player_name" => $theah->game->getPlayerNameById($card->ControllerId),
                "picked_card" => $card->getInjectCode(),
            ]);

        $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
            $card->OwnerId,
            $card->Id,
            $attachment->Id,
            $asPayment = false,
            $asPlayed = false,
            $asEffect = true
        );
        $theah->queueEvent($discardEvent);
    }

    private function clearRevealed(Theah $theah): void
    {
        $this->revealedCardIds = [];
        $this->revealedAttachmentIds = [];
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }
}
