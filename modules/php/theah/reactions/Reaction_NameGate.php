<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_NameGate extends GameReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Id = 'Reaction_NameGate';
        $this->Name = 'Name Gate';
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventApproachCharacterPlayed
        || $event instanceof EventCharacterRecruited
        || $event instanceof EventCharacterMustered)
        {
            $conflicts = $this->getNameConflicts($event->theah, $event->playerId);
            if (count($conflicts) > 0)
            {
                $transition = EventFactory::createReactionTransitionEvent($event->playerId, Game::THEAH_ID, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $conflicts = $this->getNameConflicts($theah, $theah->game->getActivePlayerId());
        foreach ($conflicts as $character)
        {
            $label = $this->getCharacterButtonLabel($theah->game, $character);
            $button = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Destroy %s'), $label), 'destroyCharacter_' . $character->Id);
            if ($character instanceof Leader)
            {
                $button['disabled'] = true;
            }
            $array[] = $button;
        }

        return $array;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} have multiple characters in play with the same name and must choose one to destroy: ');
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        $characterId = explode('_', $reactionId)[1];
        $character = $game->theah->getCardById($characterId);
        if ( ! $character instanceof Character)
        {
            throw new \Bga\GameFramework\UserException($game->translate("Selection is not a character."));
        }
        if ($character instanceof Leader)
        {
            throw new \Bga\GameFramework\UserException($game->translate("A Leader cannot be destroyed."));
        }

        $character->unEquipAllAttachments($game->theah);
        $event = EventFactory::createCharacterDestroyedEvent($game->getActivePlayerId(), $character->Id, $game->translate('Two Characters with the same name cannot be in play at the same time.'));
        $game->theah->queueEvent($event);
        $game->gamestate->nextState("done");
    }

    private function getNameConflicts(Theah $theah, int $playerId): array
    {
        $characters = $theah->getCharactersInPlayByPlayerId($playerId);

        $byName = [];
        foreach ($characters as $character)
        {
            $byName[$character->Name][] = $character;
        }

        $conflicts = [];
        foreach ($byName as $group)
        {
            if (count($group) >= 2)
            {
                foreach ($group as $character)
                {
                    $conflicts[] = $character;
                }
            }
        }
        return $conflicts;
    }

    private function getCharacterButtonLabel(Game $game, Character $character): string
    {
        if ($character instanceof Leader)
        {
            return sprintf($game->translate('%s (Leader)'), $character->Name);
        }
        if ($character instanceof CityCharacter)
        {
            return sprintf($game->translate('%s (City Character)'), $character->Name);
        }
        return $character->Name;
    }
}
