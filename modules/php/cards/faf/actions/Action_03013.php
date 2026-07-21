<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03013 extends CharacterAction
{
    /** @var int[] characterIds currently tagged Sorcerer by this Action */
    private array $TaggedOpposingIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("(Continuous) Opposing characters at this location are considered Sorcerers");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $daniella = $this->getOwningCharacter($theah);
        return $theah->cardInCity($daniella);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $this->tagOpposingAsSorcerer($event->theah);
            $event->theah->game->globals->set(Game::EXTRA_ACTIONS, 1);
        }

        // Trait persists for the duration of the player's turn — clear at turn end.
        if ($event instanceof EventPlayerTurnEnd)
        {
            $this->untagOpposingSorcerers($event->theah);

            //Can use again next turn
            $this->setUsed($event->theah, false);
        }

        // Daniella leaves play / location → drop any outstanding tags so we don't
        // leave a Sorcerer trait orphaned on a character that no longer opposes her.
        $owner = $this->getOwningCharacter($event->theah);
        if ($event instanceof EventCardMoved && $owner !== null && $event->cardId === $owner->Id)
        {
            $this->untagOpposingSorcerers($event->theah);

            //Can use again in new location
            $this->setUsed($event->theah, false);
        }
        
        if ($event instanceof EventCharacterDestroyed && $owner !== null && $event->characterId === $owner->Id)
        {
            $this->untagOpposingSorcerers($event->theah);
        }
    }

    private function tagOpposingAsSorcerer(Theah $theah): void
    {
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null) return;

        $game = $theah->game;
        $opposing = array_filter(
            $theah->getCharactersAtLocation($owner->Location),
            fn($c) => $c->ControllerId !== $owner->ControllerId
                && ! in_array($c->Id, $this->TaggedOpposingIds, true)
                && ! $c->hasTrait("Sorcerer")
        );
        foreach ($opposing as $c)
        {
            $c->addTrait($game, "Sorcerer");
            $this->TaggedOpposingIds[] = $c->Id;
        }
        if (! empty($opposing))
        {
            $owner->IsUpdated = true;
        }
    }

    private function untagOpposingSorcerers(Theah $theah): void
    {
        if (empty($this->TaggedOpposingIds)) return;
        $game = $theah->game;
        foreach ($this->TaggedOpposingIds as $cid)
        {
            $c = $theah->getCharacterById($cid);
            if ($c !== null) $c->removeTrait($game, "Sorcerer");
        }
        $this->TaggedOpposingIds = [];

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null) $owner->IsUpdated = true;
    }
}
