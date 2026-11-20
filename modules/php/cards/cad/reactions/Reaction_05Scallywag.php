<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Scallywag extends CardReaction
{
    // WHY: Capture at wound time — lethal can move the Leader before the player clicks.
    private string $leaderLocation = '';

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no translate (hide from translators).
        $this->Name = "Muster Pirate after Leader wound from your challenge";
    }

    public function getReactionDescription(Theah $theah): string
    {
        $location = $this->leaderLocation !== '' ? $this->leaderLocation : 'their location';

        return parent::getReactionDescription($theah)
            . '${you} may Muster a Pirate engaged at ' . $location . ': ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return $array;
        }

        foreach ($this->getMusterablePirates($theah, $owner->ControllerId) as $pirate)
        {
            $array[] = $this->createButtonProperty(
                $theah->game,
                'Muster ' . $pirate->Name,
                'muster-' . $pirate->Id
            );
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    /**
     * Muster = Approach Deck when the printed text does not name another source.
     *
     * @return list<Character>
     */
    public function getMusterablePirates(Theah $theah, int $playerId): array
    {
        $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_APPROACH, $playerId);

        return array_values(array_filter(
            $cards,
            fn($card) => $card instanceof Character && $card->hasTrait('Pirate')
        ));
    }

    /**
     * Your performer issued the challenge; the wounded Leader is that challenge's
     * target (refuse / pre-duel wounds) or a participant in the resulting duel.
     */
    private function isFromYourPerformerChallengeOrDuel(Theah $theah, Character $wounded, int $ownerPlayerId): bool
    {
        $inDuel = (bool) $theah->game->globals->get(Game::IN_DUEL, false);

        if ($inDuel)
        {
            $challengerId = $theah->getDuelChallengerId();
            $defenderId = $theah->getDuelDefenderId();
            $challenger = $challengerId ? $theah->getCharacterById((int) $challengerId) : null;
            if ($challenger === null || $challenger->ControllerId != $ownerPlayerId)
            {
                return false;
            }

            return $wounded->Id == (int) $challengerId || $wounded->Id == (int) $defenderId;
        }

        $performerId = (int) $theah->game->globals->get(Game::CHOSEN_PERFORMER, 0);
        $performer = $performerId > 0 ? $theah->getCharacterById($performerId) : null;
        if ($performer === null || $performer->ControllerId != $ownerPlayerId)
        {
            return false;
        }

        $targetId = (int) $theah->game->globals->get(Game::CHOSEN_TARGET, 0);

        return $targetId > 0 && $wounded->Id == $targetId;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterWounded && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }

            // Scheme must still be the chosen scheme (at Home for the day).
            if ($owner->Location != Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            $wounded = $event->theah->getCharacterById($event->characterId);
            if ($wounded === null)
            {
                return;
            }

            if (! $wounded->hasTrait('Leader'))
            {
                return;
            }

            if ($wounded->ControllerId == $owner->ControllerId)
            {
                return;
            }

            if (! $this->isFromYourPerformerChallengeOrDuel($event->theah, $wounded, $owner->ControllerId))
            {
                return;
            }

            if (count($this->getMusterablePirates($event->theah, $owner->ControllerId)) === 0)
            {
                return;
            }

            $this->leaderLocation = $wounded->Location;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState('done');
            return;
        }

        if ($reactionId === 'pass')
        {
            $this->leaderLocation = '';
            $owner->IsUpdated = true;
            $game->gamestate->nextState('done');
            return;
        }

        if (! str_starts_with($reactionId, 'muster-'))
        {
            throw new UserException('Invalid choice.');
        }

        $pirateId = (int) substr($reactionId, strlen('muster-'));
        $pirates = $this->getMusterablePirates($game->theah, $owner->ControllerId);
        $pirateIds = array_map(fn(Character $c) => $c->Id, $pirates);
        if (! in_array($pirateId, $pirateIds, true))
        {
            throw new UserException('Invalid Pirate.');
        }

        $location = $this->leaderLocation;
        if ($location === '')
        {
            throw new UserException('No location captured for the wounded Leader.');
        }

        $pirate = $game->theah->getCharacterById($pirateId);

        $game->notify->all('message', '${reaction_inject_code}: ${player_name} Musters ${pirate_inject_code} engaged at ${location}.', [
            'i18n' => ['location'],
            'reaction_inject_code' => $owner->getInjectCode(),
            'player_name' => $game->getPlayerNameById($owner->ControllerId),
            'pirate_inject_code' => $pirate !== null ? $pirate->getInjectCode() : '?',
            'location' => $location,
        ]);

        $musterEvent = EventFactory::createCharacterMusteredEvent($owner->ControllerId, $pirateId, $location);
        $game->theah->queueEvent($musterEvent);

        // WHY: Printed "engaged" — muster enters En Garde by default; engage after.
        $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $pirateId, $owner->Id, $this->Id);
        $game->theah->queueEvent($engageEvent);

        $this->setUsed($game->theah, true);
        $this->leaderLocation = '';
        $owner->IsUpdated = true;

        $game->gamestate->nextState('done');
    }
}
