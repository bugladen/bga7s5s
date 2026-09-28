<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterLostBrute;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_CrewCapLimit extends GameReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Id = 'Reaction_CrewCapLimit';
        $this->Name = 'Crew Cap Limit';
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventApproachCharacterPlayed 
        || $event instanceof EventCharacterRecruited
        || $event instanceof EventCharacterMustered
        || $event instanceof EventCharacterLostBrute)
        {
            $count = $event->theah->getCharacterCountByPlayerId($event->playerId);
            $leader = $event->theah->getLeaderByPlayerId($event->playerId);

            if ($count > $leader->ModifiedCrewCap )
            {
                $transition = EventFactory::createReactionTransitionEvent($event->playerId, Game::THEAH_ID, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }    

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        // Get non-leaders that count against Crew Cap (Brutes do not).
        $characters = $theah->getCharactersInPlayByPlayerId($theah->game->getActivePlayerId());
        $characters = array_filter(
            $characters,
            fn($character) => ! $character instanceof Leader && ! $character->hasTrait("Brute")
        );
        foreach ($characters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Sink %s'), $character->Name), 'sinkCharacter_' . $character->Id);
        }

        return $array;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} are over your Crew Cap Limit and must choose to sink a character: ');
    }
    
    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        $characterId = explode('_', $reactionId)[1];
        $character = $game->theah->getCardById($characterId);
        if ( ! $character instanceof Character)
        {
            throw new \BgaUserException($game->translate("Selection is not a character."));
        }

        $character->unEquipAllAttachments($game->theah);
        $playerId = $game->getActivePlayerId();

        // WHY: Crew-cap overage is a forced sink to The Locker, not a destroy.
        // Destroy triggers ("when a character is destroyed") must not fire.
        // CardSentToLocker moves them without EventCharacterDestroyed.
        $event = EventFactory::createCardSentToLockerEvent($playerId, $character->Id);
        $game->theah->queueEvent($event);

        // WHY: Only Planning — this reaction can also fire on High Drama recruit / Lost Brute.
        if ((int) $game->getGameStateValue(Game::TURN_PHASE) === Game::PLANNING) {
            $game->bga->playerStats->inc(Game::STAT_CREW_CAP_OVERAGE_LOCKER, 1, $playerId);
        }

        // WHY: Locker event is queued, not processed yet — subtract this sink from the live count.
        // Re-prompt until at or under cap (e.g. LostBrute can put a player several over at once).
        $count = $game->theah->getCharacterCountByPlayerId($playerId);
        $leader = $game->theah->getLeaderByPlayerId($playerId);
        $remaining = $character->hasTrait("Brute") ? $count : $count - 1;
        if ($remaining > $leader->ModifiedCrewCap)
        {
            $transition = EventFactory::createReactionTransitionEvent($playerId, Game::THEAH_ID, $this->Id);
            $game->theah->queueEvent($transition);
        }

        $game->gamestate->nextState("done");
    }
}
        