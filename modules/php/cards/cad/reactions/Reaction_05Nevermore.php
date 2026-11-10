<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Timebomb;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05TimebombB;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Nevermore extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate.
        $this->Name = "Opponent musters Klim Grabiezca";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP (Snow White / Volk).
        return parent::getReactionDescription($theah)
            . '${you} may choose an opponent to Muster Klim Grabiezca from out of play: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return $array;
        }

        // WHY: Buttons only — opponent list is small; no follow-on GameState (Snow White shape).
        foreach ($this->getOpponentChoices($theah->game, $owner->ControllerId) as $opponent)
        {
            $array[] = $this->createButtonProperty(
                $theah->game,
                sprintf('Muster Klim for %s', $opponent['name']),
                'opponent-' . $opponent['id']
            );
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function getOpponentChoices(Game $game, int $controllerId): array
    {
        $opponents = [];
        foreach ($game->loadPlayersBasicInfos() as $playerId => $player)
        {
            $playerId = (int) $playerId;
            if ($playerId == $controllerId)
            {
                continue;
            }

            $opponents[] = [
                'id' => $playerId,
                'name' => $player['player_name'],
            ];
        }

        return $opponents;
    }

    /**
     * @return list<Character>
     */
    private function getControlledVillains(Theah $theah, int $controllerId): array
    {
        return array_values(array_filter(
            $theah->getCharactersInPlayByPlayerId($controllerId),
            fn(Character $character) => $character->hasTrait("Villain")
        ));
    }

    /**
     * Any Klim A-side or Porcelain Heart copy in world / locker / discard.
     */
    private function findKlimInstance(Theah $theah): ?Card
    {
        foreach ($theah->getAllCards() as $card)
        {
            if ($card instanceof _05Timebomb || $card instanceof _05TimebombB)
            {
                return $card;
            }
        }

        foreach ($theah->game->loadPlayersBasicInfos() as $playerId => $_)
        {
            $playerId = (int) $playerId;
            $lockerName = $theah->game->getPlayerLockerName($playerId);
            foreach ($theah->getCardObjectsAtLocation($lockerName) as $card)
            {
                if ($card instanceof _05Timebomb || $card instanceof _05TimebombB)
                {
                    return $card;
                }
            }

            $discardName = $theah->game->getPlayerDiscardDeckName($playerId);
            foreach ($theah->getCardObjectsAtLocation($discardName) as $card)
            {
                if ($card instanceof _05Timebomb || $card instanceof _05TimebombB)
                {
                    return $card;
                }
            }
        }

        return null;
    }

    /**
     * True when Klim A-side can be created or remustered (not already in play / Heart).
     */
    private function isKlimMusterable(Theah $theah): bool
    {
        $klim = $this->findKlimInstance($theah);
        if ($klim === null)
        {
            return true;
        }

        // WHY: Flipped Porcelain Heart is the same card identity — cannot muster Klim while Heart exists.
        if ($klim instanceof _05TimebombB)
        {
            return false;
        }

        if (! ($klim instanceof _05Timebomb))
        {
            return false;
        }

        return ! $theah->cardInPlay($klim);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Printed "At the end of Planning" (retimed from Dawn — schemes are at Home
        // after Planning reveal; Snow White Planning-End shape).
        if ($event instanceof EventPhasePlanningEnd && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }

            // Chosen schemes sit at Home until Dusk locker (_01098 / _05SnowWhite).
            if ($owner->Location != Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            // WHY: Villain Reaction = mechanical trait gate (not ISorcererAbility) —
            // Merchant/Sorcerer Planning-End shape: ≥1 controlled traited character.
            if (count($this->getControlledVillains($event->theah, $owner->ControllerId)) < 1)
            {
                return;
            }

            if (! $this->isKlimMusterable($event->theah))
            {
                return;
            }

            if (count($this->getOpponentChoices($event->theah->game, $owner->ControllerId)) < 1)
            {
                return;
            }

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId === 'pass')
        {
            // Pass does not consume setUsed — EventPhasePlanningEnd will not re-fire today.
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'opponent-'))
        {
            $opponentId = (int) substr($reactionId, strlen('opponent-'));
            if ($owner === null || $owner->Location != Game::LOCATION_PLAYER_HOME)
            {
                $game->gamestate->nextState("done");
                return;
            }

            $schemeControllerId = $owner->ControllerId;
            if ($opponentId == $schemeControllerId
                || ! isset($game->loadPlayersBasicInfos()[$opponentId]))
            {
                $game->gamestate->nextState("done");
                return;
            }

            if (count($this->getControlledVillains($game->theah, $schemeControllerId)) < 1)
            {
                $game->gamestate->nextState("done");
                return;
            }

            if (! $this->isKlimMusterable($game->theah))
            {
                $game->notify->all("message", '${scheme_inject_code}: Klim Grabiezca is not available to Muster.', [
                    "scheme_inject_code" => $owner->getInjectCode(),
                ]);
                $game->gamestate->nextState("done");
                return;
            }

            $klim = $this->findKlimInstance($game->theah);

            // WHY: Klim cannot enter Approach — first muster creates from out of play
            // (Snow White / Damned createCardInLocation shape). Remuster from locker/discard
            // when a prior A-side copy exists. OwnerId stays the scheme controller so
            // Porcelain Heart flip returns control to the Nevermore player (Timebomb flip WHY).
            if ($klim === null)
            {
                $klim = $game->createCardInLocation(
                    '05Timebomb',
                    Game::LOCATION_PURGATORY,
                    $schemeControllerId,
                    $opponentId
                );
                $game->theah->addCardToWorld($klim);
            }
            else
            {
                if (! ($klim instanceof _05Timebomb))
                {
                    $game->gamestate->nextState("done");
                    return;
                }

                $game->theah->addCardToWorld($klim);

                // WHY: Scan every player's locker/discard — OwnerId may not match location_arg
                // after cross-player control (Snow White only searches the mustering player).
                foreach ($game->loadPlayersBasicInfos() as $playerId => $_)
                {
                    $playerId = (int) $playerId;
                    $playerLocker = $game->getPlayerLockerName($playerId);
                    $playerDiscard = $game->getPlayerDiscardDeckName($playerId);

                    if ($klim->Location === $playerLocker)
                    {
                        $remove = EventFactory::createCardRemovedFromLockerEvent($playerId, $klim->Id);
                        $game->theah->queueEvent($remove);
                        break;
                    }

                    if ($klim->Location === $playerDiscard)
                    {
                        $remove = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $klim->Id);
                        $game->theah->queueEvent($remove);
                        break;
                    }
                }

                // Preserve OwnerId as scheme controller when remustering a copy that drifted.
                if ($klim->OwnerId == 0)
                {
                    $klim->OwnerId = $schemeControllerId;
                    $klim->IsUpdated = true;
                }
            }

            $game->notify->all("message", '${scheme_inject_code}: ${opponent_name} Musters ${character_inject_code} from out of play and takes control of him.', [
                "scheme_inject_code" => $owner->getInjectCode(),
                "opponent_name" => $game->getPlayerNameById($opponentId),
                "character_inject_code" => $klim->getInjectCode(),
            ]);

            // WHY: Muster playerId = opponent — EventHub sets ControllerId from playerId.
            // OwnerId is left alone so flip→Heart returns to the Nevermore controller.
            $musterEvent = EventFactory::createCharacterMusteredEvent(
                $opponentId,
                $klim->Id,
                Game::LOCATION_PLAYER_HOME
            );
            $game->theah->queueEvent($musterEvent);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
