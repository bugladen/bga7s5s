<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
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
            || $event instanceof EventCharacterDestroyed)
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

        //Get all non-leaders to sink
        $characters = $theah->getCharactersInPlayByPlayerId($theah->game->getActivePlayerId());
        $characters = array_filter($characters, fn($character) => ! $character instanceof Leader);
        foreach ($characters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, 'Sink ' . $character->Name, 'sinkCharacter_' . $character->Id);
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

        $event = EventFactory::createCharacterDestroyedEvent($game->getActivePlayerId(), $character->Id, $game->translate('Chosen to sink for Crew Cap Limit'));
        $game->theah->queueEvent($event);
        $game->gamestate->nextState("done");
}
}
        